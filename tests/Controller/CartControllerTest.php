<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartControllerTest extends WebTestCase
{
    private $testUser;
    private $testProduct;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = $this->createTestUser();
        $this->testProduct = $this->createTestProduct();
    }

    public function testAddToCartValidation(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('POST', '/cart/add/'.$this->testProduct->getId(), [
            'size' => 'M',
            'quantity' => 0
        ]);
        $this->assertResponseRedirects();
    }

    public function testCartOperations(): void
    {
        $this->client->loginUser($this->testUser);

        // 1. Ajouter au panier
        $this->client->request('POST', '/cart/add/'.$this->testProduct->getId(), [
            'size' => 'M',
            'quantity' => 2
        ]);
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        // 2. Vérifier le contenu du panier
        $cartKey = $this->testProduct->getId() . '_M';
        $cart = $this->client->getRequest()->getSession()->get('cart', []);
        
        $this->assertArrayHasKey($cartKey, $cart, 'Le produit devrait être dans le panier');
        $this->assertEquals(2, $cart[$cartKey]['quantity'], 'La quantité devrait être 2');
        
        // 3. Supprimer du panier
        $this->client->request('GET', '/cart/remove/'.$this->testProduct->getId().'/M');
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        // 4. Vérifier que le panier est vide
        $cart = $this->client->getRequest()->getSession()->get('cart', []);
        $this->assertEmpty($cart, 'Le panier devrait être vide');
    }

    public function testCartTotal(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('POST', '/cart/add/'.$this->testProduct->getId(), [
            'size' => 'M',
            'quantity' => 2
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $cart = $this->client->getRequest()->getSession()->get('cart', []);
        $total = array_reduce($cart, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);

        $this->assertEquals(59.80, $total, 'Le total devrait être 59.80');
    }
}