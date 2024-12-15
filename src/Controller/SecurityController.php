<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Vérifier si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            // Vérifier si l'utilisateur a le rôle ROLE_ADMIN
            if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                // Si l'utilisateur est admin, le rediriger vers le back-office
                return $this->redirectToRoute('admin_sweatshirt_create'); // Assurez-vous que cette route existe
            }

            // Si l'utilisateur n'est pas admin, le rediriger vers la page d'accueil
            return $this->redirectToRoute('home'); // Ou la page d'accueil spécifique pour les utilisateurs
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Récupérer le dernier nom d'utilisateur saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        // Rendre le formulaire de connexion
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
