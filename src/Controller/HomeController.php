<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SweatshirtRepository $sweatshirtRepository): Response
    {
        // Récupère les sweat-shirts mis en avant
        $featuredSweatshirts = $sweatshirtRepository->findBy(['featured' => true], null, 3);


        return $this->render('home/index.html.twig', [
            'featuredSweatshirts' => $featuredSweatshirts,
            'companyName' => 'Stubborn',
            'slogan' => 'Don\'t compromise on your look',
        ]);
    }
}
