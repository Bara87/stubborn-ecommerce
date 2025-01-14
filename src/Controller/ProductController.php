<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(SweatshirtRepository $repository, Request $request): Response
    {
        $priceRange = $request->query->get('priceRange');
        
        // Définir les plages de prix en fonction de la sélection
        if ($priceRange) {
            list($minPrice, $maxPrice) = explode('-', $priceRange);
            $products = $repository->findByPriceRange((float)$minPrice, (float)$maxPrice);
        } else {
            $products = $repository->findAll();
        }

        return $this->render('product/list.html.twig', [
            'products' => $products,
            'selectedRange' => $priceRange // Pour maintenir la sélection dans le template
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
public function detail(int $id, SweatshirtRepository $repository): Response
{
    // Récupérer le produit par son ID
    $product = $repository->find($id);

    if (!$product) {
        throw $this->createNotFoundException('Ce produit n\'existe pas.');
    }

    return $this->render('product/detail.html.twig', [
        'product' => $product,
    ]);
}

}