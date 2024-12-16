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

    // Injection de l'EntityManagerInterface dans le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        // Vérifier que la taille et la quantité existent
        if (!$size) {
            $this->addFlash('danger', 'Veuillez sélectionner une taille.');
            return $this->redirectToRoute('app_product_detail', ['id' => $id]);
        }

        // Vérifier si la quantité demandée est disponible dans le stock
        $sizes = $product->getSizes();
        if (!isset($sizes[$size]) || $quantity > $sizes[$size]) {
            $this->addFlash('danger', 'La quantité demandée dépasse le stock disponible.');
            return $this->redirectToRoute('app_product_detail', ['id' => $id]);
        }

        // Ajouter ou mettre à jour le produit dans le panier
        $cartKey = $id . '_' . $size; // Clé unique basée sur l'ID et la taille
        if (isset($cart[$cartKey])) {
            // Si le produit est déjà dans le panier, on met à jour la quantité
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            // Sinon, on ajoute le produit avec la quantité demandée
            $cart[$cartKey] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'size' => $size,
                'quantity' => $quantity,
            ];
        }

        // Sauvegarder les modifications dans la session
        $session->set('cart', $cart);

        // Mise à jour du stock en base de données
        $sizes[$size] -= $quantity;
        if ($sizes[$size] < 0) {
            $sizes[$size] = 0; // Empêcher un stock négatif
        }
        $product->setSizes($sizes);

        // Enregistrer les modifications dans la base de données
        $this->entityManager->flush();

        $this->addFlash('success', 'Produit ajouté au panier.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}/{size}', name: 'app_cart_remove')]
    public function remove(int $id, string $size, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        // Utiliser la clé unique basée sur l'ID et la taille pour trouver et supprimer l'élément
        $cartKey = $id . '_' . $size;

        // Vérifier si le produit existe dans le panier
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]); // Supprimer l'article
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

        // Calculer le total
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Passer le total à la session, si nécessaire
        $session->set('checkout_total', $total);

        // Afficher la page de checkout (panier) avant la redirection vers le paiement
    return $this->render('cart/checkout.html.twig', [
        'cart' => $cart,
        'total' => $total,
    ]);
    }
}