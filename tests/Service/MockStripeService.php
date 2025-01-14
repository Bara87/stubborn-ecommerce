<?php

namespace App\Tests\Service;

use App\Service\StripeService;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class MockStripeService extends StripeService
{
    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        parent::__construct($params, $logger);
    }

    public function createTestCheckoutSession(array $cart, string $userId): Session
    {
        // Retourner une fausse session Stripe pour les tests
        return new Session([
            'id' => 'test_session_' . uniqid(),
            'url' => 'https://test.stripe.com/checkout',
            'payment_status' => 'paid',
            'metadata' => ['is_test' => 'true']
        ]);
    }

    public function verifyTestPayment(string $sessionId): bool
    {
        // Toujours retourner true pour les tests
        return true;
    }

    public function getPublicKey(): string
    {
        return 'pk_test_mock_key';
    }
} 