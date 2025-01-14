<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeService
{
    private $secretKey;
    private $publicKey;
    private $logger;

    public function __construct(
        private ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        // Vérification des clés Stripe
        if (!$params->has('stripe_test_secret_key') || !$params->has('stripe_test_public_key')) {
            throw new \RuntimeException('Les clés Stripe ne sont pas configurées');
        }

        $this->secretKey = $params->get('stripe_test_secret_key');
        $this->publicKey = $params->get('stripe_test_public_key');
        $this->logger = $logger;

        // Initialisation de Stripe
        try {
            Stripe::setApiKey($this->secretKey);
        } catch (\Exception $e) {
            $this->logger->error('Erreur d\'initialisation Stripe', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Impossible d\'initialiser Stripe');
        }
    }

    public function createTestCheckoutSession(array $cart, string $userId): ?Session
    {
        if (empty($cart)) {
            throw new BadRequestHttpException('Le panier est vide');
        }

        try {
            $lineItems = $this->prepareLineItems($cart);
            
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $this->params->get('app.domain') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->params->get('app.domain') . '/payment/cancel',
                'metadata' => [
                    'user_id' => $userId,
                    'is_test' => 'true',
                    'cart_items' => json_encode($cart) // Sauvegarde du panier pour référence
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'is_test' => 'true',
                        'user_id' => $userId
                    ]
                ]
            ]);

            $this->logger->info('Session de paiement test créée', [
                'session_id' => $session->id,
                'user_id' => $userId,
                'cart_total' => array_reduce($cart, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0)
            ]);

            return $session;

        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur Stripe', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'cart' => $cart
            ]);
            throw new \RuntimeException('Erreur lors de la création de la session de paiement: ' . $e->getMessage());
        }
    }

    private function prepareLineItems(array $cart): array
    {
        try {
            $items = [];
            foreach ($cart as $item) {
                if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                    $this->logger->error('Article invalide', ['item' => $item]);
                    throw new \InvalidArgumentException('Article invalide dans le panier');
                }

                $items[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int)($item['price'] * 100), // Conversion en centimes
                        'product_data' => [
                            'name' => $item['name'],
                            'description' => isset($item['size']) ? 'Taille: ' . $item['size'] : null,
                        ],
                    ],
                    'quantity' => (int)$item['quantity'],
                ];
            }
            return $items;
        } catch (\Exception $e) {
            $this->logger->error('Erreur de préparation des articles', [
                'error' => $e->getMessage(),
                'cart' => $cart
            ]);
            throw new \RuntimeException('Erreur lors de la préparation des articles: ' . $e->getMessage());
        }
    }

    public function verifyTestPayment(string $sessionId): bool
    {
        if (empty($sessionId)) {
            throw new BadRequestHttpException('ID de session manquant');
        }

        try {
            $session = Session::retrieve($sessionId);
            $isValid = $session->payment_status === 'paid' && 
                      $session->metadata->is_test === 'true';

            $this->logger->info('Vérification du paiement', [
                'session_id' => $sessionId,
                'status' => $session->payment_status,
                'is_valid' => $isValid
            ]);

            return $isValid;
        } catch (ApiErrorException $e) {
            $this->logger->error('Erreur de vérification du paiement', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Erreur lors de la vérification du paiement');
        }
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
} 