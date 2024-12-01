<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    // Méthode pour initialiser le panier si nécessaire
    private function initializeCart(SessionInterface $session): array
    {
        $cart = $session->get('cart', []); // Récupère le panier ou initialise un tableau vide
        $session->set('cart', $cart); // Assure que le panier est sauvegardé
        return $cart;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []); // Récupère les articles du panier
        $total = 0;

        // Calculer le total de la commande
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
public function add(int $id, Request $request, SessionInterface $session, SweatshirtRepository $repository): Response
{
    $cart = $this->initializeCart($session);

    $product = $repository->find($id);

    if (!$product) {
        throw $this->createNotFoundException('Produit introuvable.');
    }

    // Récupérer la taille depuis la requête POST
    $size = $request->request->get('size');

    if (!$size) {
        $this->addFlash('danger', 'Veuillez sélectionner une taille.');
        return $this->redirectToRoute('app_product_detail', ['id' => $id]);
    }

    // Ajouter ou mettre à jour le produit dans le panier
    $cartKey = $id . '_' . $size; // Clé unique basée sur l'ID et la taille
    if (isset($cart[$cartKey])) {
        $cart[$cartKey]['quantity']++;
    } else {
        $cart[$cartKey] = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'size' => $size,
            'quantity' => 1,
        ];
    }

    $session->set('cart', $cart);

    $this->addFlash('success', 'Produit ajouté au panier.');
    return $this->redirectToRoute('app_cart');
}


    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, SessionInterface $session): Response
    {
        $cart = $this->initializeCart($session);

        // Vérifier si le produit existe dans le panier
        if (isset($cart[$id])) {
            unset($cart[$id]); // Supprimer l'article
            $session->set('cart', $cart);

            $this->addFlash('success', 'Produit retiré du panier.');
        } else {
            $this->addFlash('warning', 'Produit non trouvé dans le panier.');
        }

        return $this->redirectToRoute('app_cart'); // Rediriger vers la page du panier
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        // Logique pour la validation ou redirection vers le paiement
        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
        ]);
    }
}
