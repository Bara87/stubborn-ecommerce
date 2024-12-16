<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CartController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []); // Récupère le panier
        $total = 0;

        // Calcul du total de la commande
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function addToCart(int $id, Request $request, SessionInterface $session, SweatshirtRepository $repository): Response
    {
        $cart = $session->get('cart', []); // Récupère le panier ou initialise un tableau vide
    
        // Récupérer le produit par ID
        $product = $repository->find($id);
    
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }
    
        // Récupérer la taille et la quantité depuis la requête POST
        $size = $request->request->get('size');
        $quantity = (int) $request->request->get('quantity');
    
        if (!$size) {
            $this->addFlash('danger', 'Veuillez sélectionner une taille.');
            return $this->redirectToRoute('app_product_detail', ['id' => $id]);
        }
    
        $sizes = $product->getSizes();
        if (!isset($sizes[$size]) || $quantity > $sizes[$size]) {
            $this->addFlash('danger', 'La quantité demandée dépasse le stock disponible.');
            return $this->redirectToRoute('app_product_detail', ['id' => $id]);
        }
    
        $cartKey = $id . '_' . $size; // Clé unique basée sur l'ID et la taille
        
    
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'id' => $product->getId(),
                'imagePath' => $product->getImagePath(), // Ajout de l'image
                'name' => $product->getName(),
                'price' => $product->getPrice(),    
                'size' => $size,
                'quantity' => $quantity,
            ];
        }
    
        $session->set('cart', $cart);
    
        $sizes[$size] -= $quantity;
        $product->setSizes($sizes);
    
        $this->entityManager->flush();
    
        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('app_cart');
    }
    

    #[Route('/cart/remove/{id}/{size}', name: 'app_cart_remove')]
    public function remove(int $id, string $size, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        $cartKey = $id . '_' . $size;

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            $session->set('cart', $cart);

            $this->addFlash('success', 'Produit retiré du panier.');
        } else {
            $this->addFlash('warning', 'Produit non trouvé dans le panier.');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

       
    }
}