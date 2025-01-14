<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\StripeService;
use App\Tests\Service\MockStripeService;

class PaymentControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient([
            'environment' => 'test'
        ]);
        
        // Remplacer le service Stripe par notre mock
        self::getContainer()->set(
            StripeService::class,
            new MockStripeService(
                self::getContainer()->get('parameter_bag'),
                self::getContainer()->get('logger')
            )
        );

        // Récupérer l'entity manager
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Créer l'utilisateur de test
        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setName('Test User');
        
        // Hasher le mot de passe
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->testUser, 'test123');
        $this->testUser->setPassword($hashedPassword);
        
        // Persister l'utilisateur
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testPaymentPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/payment');
        $this->assertResponseRedirects('/login');
    }

    public function testCreateCheckoutSessionWithEmptyCart(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('POST', '/create-checkout-session');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testCreateCheckoutSessionWithValidCart(): void
    {
        $this->client->loginUser($this->testUser);

        // Simuler un panier dans la session
        $session = $this->client->getRequest()->getSession();
        $session->set('cart', [
            '1_M' => [
                'id' => 1,
                'name' => 'Test Sweatshirt',
                'price' => 29.90,
                'quantity' => 1,
                'size' => 'M',
                'imagePath' => 'test.jpg'
            ]
        ]);

        $this->client->request('POST', '/create-checkout-session');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('url', $responseData);
    }

    public function testPaymentSuccess(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/payment/success', [
            'session_id' => 'test_session_id'
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPaymentCancel(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/payment/cancel');
        $this->assertResponseIsSuccessful();
    }

    protected function tearDown(): void
    {
        if ($this->testUser) {
            $this->entityManager->remove($this->testUser);
            $this->entityManager->flush();
        }

        parent::tearDown();
        
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
        $this->testUser = null;
    }
} 