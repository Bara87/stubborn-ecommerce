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
        // Récupérer les filtres de la requête (par exemple, fourchette de prix)
        $minPrice = $request->query->get('minPrice', 0);
        $maxPrice = $request->query->get('maxPrice', PHP_INT_MAX);

        // Si une plage de prix est définie, appliquer le filtre
        if ($minPrice !== null && $maxPrice !== null) {
            $products = $repository->findByPriceRange($minPrice, $maxPrice);
        } else {
            // Sinon, récupérer tous les produits
            $products = $repository->findAll();
        }

        return $this->render('product/list.html.twig', [
            'products' => $products,
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