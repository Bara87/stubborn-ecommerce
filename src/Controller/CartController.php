<?php

namespace App\Controller;

use App\Repository\SweatshirtRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\StripeService;
use Stripe\Stripe;

class CartController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session, StripeService $stripeService): Response
    {
        $cart = $session->get('cart', []);
        $total = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'stripe_public_key' => $stripeService->getPublicKey()
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, SessionInterface $session, SweatshirtRepository $repository): Response
    {
        try {
            $cart = $session->get('cart', []);
            $product = $repository->find($id);
            
            if (!$product) {
                throw new \Exception('Produit introuvable.');
            }

            // Validation du prix
            if ($product->getPrice() <= 0) {
                throw new \Exception('Prix invalide pour ce produit.');
            }

            $size = $request->request->get('size');
            $quantity = (int) $request->request->get('quantity');

            if ($quantity <= 0) {
                throw new \Exception('La quantité doit être supérieure à 0.');
            }

            if (!$size) {
                throw new \Exception('Veuillez sélectionner une taille.');
            }

            $sizes = $product->getSizes();
            if (!isset($sizes[$size]) || $quantity > $sizes[$size]) {
                throw new \Exception('La quantité demandée dépasse le stock disponible.');
            }

            $cartKey = $id . '_' . $size;
            
            if (isset($cart[$cartKey])) {
                $cart[$cartKey]['quantity'] += $quantity;
            } else {
                $cart[$cartKey] = [
                    'id' => $product->getId(),
                    'imagePath' => $product->getImagePath(),
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
            return $this->redirectToRoute('cart');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('product_detail', ['id' => $id]);
        }
    }
    
    #[Route('/cart/remove/{id}/{size}', name: 'cart_remove')]
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

        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart');
        }

        $total = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(SessionInterface $session): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vider le panier
        $session->remove('cart');

        // Rediriger vers une page de confirmation
        return $this->render('cart/success.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(SessionInterface $session): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté'], 403);
        }

        $cart = $session->get('cart', []);
        if (empty($cart)) {
            return $this->json(['error' => 'Le panier est vide'], 400);
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'customer_email' => $user->getUserIdentifier(),
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Achat sur notre boutique',
                        ],
                        'unit_amount' => $this->calculateTotal($cart) * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('payment_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->json(['url' => $checkout_session->url]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function calculateTotal(array $cart): float
    {
        return array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);
    }
}