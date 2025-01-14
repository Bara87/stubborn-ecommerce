<?php

// src/Controller/RegistrationController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Role\Role;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le RepeatedType a déjà vérifié que les mots de passe correspondent
            // On récupère donc directement le mot de passe validé
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Hash du mot de passe
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);

            // Vérification du code admin
            $adminCode = $form->get('adminCode')->getData();
            if ($adminCode === $this->getParameter('app.admin_code')) {
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            // Désactiver le compte par défaut
            $user->setIsActive(false);

            // Générer un jeton d'activation
            $activationToken = bin2hex(random_bytes(32));
            $user->setActivationToken($activationToken);

            // Sauvegarder l'utilisateur en base
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoyer l'email d'activation
            $activationUrl = $this->generateUrl('app_activate_account', [
                'token' => $activationToken,
            ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->from('aliounedia2010@gmail.com')
                ->to($user->getEmail())
                ->subject('Activation de votre compte')
                ->html('<p>Pour activer votre compte, cliquez sur le lien suivant : <a href="' . $activationUrl . '">Activer mon compte</a></p>');

            $mailer->send($email);

            // Ajouter un message flash et rediriger
            $this->addFlash('success', 'Un email d\'activation a été envoyé. Veuillez vérifier votre boîte de réception.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/activate/{token}', name: 'app_activate_account')]
    public function activateAccount(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['activationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Lien d\'activation invalide ou expiré.');
            return $this->redirectToRoute('app_register');
        }

        $user->setIsActive(true);
        $user->setActivationToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été activé avec succès.');
        return $this->redirectToRoute('app_login');
    }
}
