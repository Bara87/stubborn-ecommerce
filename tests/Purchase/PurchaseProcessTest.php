<?php

namespace App\Tests\Purchase;

use App\Tests\WebTestCase;

class PurchaseProcessTest extends WebTestCase
{
    private $testUser;
    private $testProduct;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = $this->createTestUser();
        $this->testProduct = $this->createTestProduct();
    }

    public function testCompleteCheckoutProcess(): void
    {
        // 1. Connecter l'utilisateur
        $this->client->loginUser($this->testUser);

        // 2. Ajouter un produit au panier
        $this->client->request('POST', '/cart/add/'.$this->testProduct->getId(), [
            'size' => 'M',
            'quantity' => 2
        ]);
        $this->assertResponseRedirects();

        // 3. Vérifier le panier via la session
        $cart = $this->client->getRequest()->getSession()->get('cart', []);
        $cartKey = $this->testProduct->getId() . '_M';
        
        $this->assertArrayHasKey($cartKey, $cart);
        $this->assertEquals(2, $cart[$cartKey]['quantity']);
        
        // 4. Créer une session de paiement
        $this->client->request('POST', '/create-checkout-session');
        
        // On s'attend à une erreur 400 car la clé Stripe est invalide en test
        $this->assertResponseStatusCodeSame(400);
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid API Key provided', $response['error']);
    }

    public function testPaymentRequiresAuthentication(): void
    {
        $this->client->request('POST', '/create-checkout-session');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testEmptyCartCheckout(): void
    {
        $this->client->loginUser($this->testUser);
        
        $this->client->request('POST', '/create-checkout-session');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }
}