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

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            // Ajouter un rôle par défaut
            $user->setRoles(['ROLE_USER']);

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
