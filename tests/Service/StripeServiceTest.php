<?php

namespace App\Tests\Service;

use App\Service\StripeService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeServiceTest extends TestCase
{
    private $stripeService;
    private $parameterBag;
    private $logger;

    protected function setUp(): void
    {
        // Créer les mocks
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Configurer le mock ParameterBag
        $this->parameterBag->expects($this->any())
            ->method('has')
            ->willReturn(true);

        $this->parameterBag->expects($this->any())
            ->method('get')
            ->willReturnCallback(function($key) {
                return match($key) {
                    'stripe_test_secret_key' => 'sk_test_123',
                    'stripe_test_public_key' => 'pk_test_123',
                    'app.domain' => 'http://localhost',
                    default => null
                };
            });

        // Créer le service
        $this->stripeService = new StripeService(
            $this->parameterBag,
            $this->logger
        );
    }

    public function testGetPublicKey(): void
    {
        $result = $this->stripeService->getPublicKey();
        $this->assertEquals('pk_test_123', $result);
    }

    public function testCreateCheckoutSessionWithEmptyCart(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->stripeService->createTestCheckoutSession([], 'user_123');
    }

    public function testConstructorWithMissingStripeKeys(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        new StripeService($parameterBag, $this->logger);
    }

    public function testPrepareLineItemsWithInvalidCart(): void
    {
        $cart = [['invalid' => 'data']];
        
        $this->expectException(\RuntimeException::class);
        $this->stripeService->createTestCheckoutSession($cart, 'user_123');
    }

    public function testVerifyTestPaymentWithEmptySessionId(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->stripeService->verifyTestPayment('');
    }

    public function testCreateCheckoutSessionWithValidCart(): void
    {
        $cart = [
            [
                'name' => 'Test Sweatshirt',
                'price' => 29.90,
                'quantity' => 1,
                'size' => 'M'
            ]
        ];

        $this->expectException(\RuntimeException::class);
        $this->stripeService->createTestCheckoutSession($cart, 'user_123');
    }

    public function testLoggerRecordsError(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->stripeService = new StripeService(
            $this->parameterBag,
            $this->logger
        );

        // Premier appel attendu
        $this->logger->expects($this->atLeast(1))
            ->method('error')
            ->with(
                $this->stringContains('Erreur de préparation des articles'),
                $this->anything()
            );

        $cart = [['invalid' => 'data']];
        
        try {
            $this->stripeService->createTestCheckoutSession($cart, 'user_123');
            $this->fail('Une exception aurait dû être levée');
        } catch (\RuntimeException $e) {
            // L'exception est attendue
            $this->assertTrue(true);
        }
    }
}