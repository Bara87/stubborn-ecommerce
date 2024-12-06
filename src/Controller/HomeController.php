<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\SweatshirtRepository;

class HomeController extends AbstractController
{
    public function index(SweatshirtRepository $sweatshirtRepository): Response
    {   
        $user = $this->getUser();
        // Récupère trois sweat-shirts mis en avant depuis la base de données
        $featuredSweatshirts = $sweatshirtRepository->findBy(
            ['featured' => true], // Exemple de champ pour les produits mis en avant
            null,
            3
        );

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'featuredSweatshirts' => $featuredSweatshirts, // Assurez-vous de transmettre cette variable
            'companyName' => 'Stubborn',
            'slogan' => 'Don\'t compromise on your look',
        ]);
    }
}
