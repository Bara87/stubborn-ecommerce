<?php

// src/Controller/PaymentController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaymentController extends AbstractController
{
    #[Route('/payment', name: 'payment')]
    public function payment(): Response
    {
        return $this->render('payment/payment.html.twig');
    }

    #[Route('/create-checkout-session', name: 'create_checkout_session')]
    public function createCheckoutSession(SessionInterface $session): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);  // Utilisez la clé secrète

        // Récupérer le panier depuis la session
        $cart = $session->get('cart', []);
        $total = $session->get('checkout_total', 0);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        try {
            // Créer une session de paiement
            $lineItems = [];
            foreach ($cart as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                        'unit_amount' => $item['price'] * 100, // Montant en centimes
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            $sessionStripe = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://127.0.0.1:8000/payment/success',
                'cancel_url' => 'http://127.0.0.1:8000/payment/cancel',
            ]);

            // Rediriger vers la page de paiement Stripe
            return $this->redirect($sessionStripe->url);
        } catch (\Exception $e) {
            return new Response('Erreur lors de la création de la session de paiement: ' . $e->getMessage());
        }
    }

    #[Route('payment/success', name: 'payment_success')]
    public function success(): Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
