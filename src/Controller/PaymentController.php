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
use App\Service\StripeService;
use Psr\Log\LoggerInterface;

class PaymentController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/payment', name: 'payment')]
    public function payment(): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer un paiement');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('payment/payment.html.twig');
    }

    #[Route('/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(
        Request $request,
        SessionInterface $session,
        StripeService $stripeService
    ): Response {
        try {
            if (!$this->getUser()) {
                throw new \Exception('Vous devez être connecté pour effectuer un paiement');
            }

            $cart = $session->get('cart', []);
            $this->logger->info('Tentative de création de session de paiement', [
                'user_id' => $this->getUser()->getUserIdentifier(),
                'cart' => $cart
            ]);

            if (empty($cart)) {
                throw new \Exception('Le panier est vide');
            }

            $checkoutSession = $stripeService->createTestCheckoutSession(
                $cart,
                $this->getUser()->getUserIdentifier()
            );

            if (!$checkoutSession) {
                throw new \Exception('Erreur lors de la création de la session de paiement');
            }

            // Ne vider que le panier, pas toute la session
            $session->remove('cart');
            
            return $this->json([
                'id' => $checkoutSession->id,
                'url' => $checkoutSession->url
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du processus de paiement', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'anonymous'
            ]);

            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function success(Request $request, StripeService $stripeService): Response
    {
        try {
            $sessionId = $request->query->get('session_id');
            
            if (!$sessionId) {
                throw new \Exception('Session ID manquant');
            }

            if ($stripeService->verifyTestPayment($sessionId)) {
                // Assurez-vous que l'utilisateur reste connecté
                if (!$this->getUser()) {
                    return $this->redirectToRoute('app_login');
                }

                $this->addFlash('success', 'Paiement effectué avec succès !');
                return $this->render('payment/success.html.twig');
            }

            throw new \Exception('Paiement non vérifié');

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du paiement', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId ?? null
            ]);
            
            $this->addFlash('error', 'Une erreur est survenue lors de la vérification du paiement');
            return $this->redirectToRoute('cart');
        }
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé');
        return $this->render('payment/cancel.html.twig');
    }
}
