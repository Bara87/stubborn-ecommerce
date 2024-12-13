<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CartControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(); // Crée un client pour envoyer des requêtes HTTP
    }

    public function testAddToCart(): void
    {
        // Créer un produit fictif
        $productId = 1;
        $size = 'M';
        $quantity = 2;

        // Effectuer une requête POST pour ajouter le produit au panier
        $crawler = $this->client->request('POST', '/cart/add/'.$productId, [
            'size' => $size,
            'quantity' => $quantity,
        ]);

        // Vérifier que la réponse est correcte
        $this->assertResponseIsSuccessful(); // Vérifie si la réponse HTTP est 200 OK

        // Vérifier si le produit est bien ajouté au panier dans la session
        $session = $this->client->getContainer()->get('session');
        $cart = $session->get('cart', []);
        
        // Vérifier si le produit est dans le panier
        $this->assertArrayHasKey($productId . '_' . $size, $cart);
        $this->assertEquals($quantity, $cart[$productId . '_' . $size]['quantity']);
    }

    public function testAddToCartWithInvalidQuantity(): void
    {
        // Test avec une quantité invalide qui dépasse le stock
        $productId = 1;
        $size = 'M';
        $quantity = 999; // Quantité largement supérieure au stock

        // Effectuer une requête POST
        $crawler = $this->client->request('POST', '/cart/add/'.$productId, [
            'size' => $size,
            'quantity' => $quantity,
        ]);

        // Vérifier que la réponse contient un message d'erreur
        $this->assertSelectorTextContains('.alert-danger', 'La quantité demandée dépasse le stock disponible');
    }

    public function testRemoveFromCart(): void
    {
        // Ajouter un produit au panier avant de le retirer
        $productId = 1;
        $size = 'M';
        $quantity = 2;

        $this->client->request('POST', '/cart/add/'.$productId, [
            'size' => $size,
            'quantity' => $quantity,
        ]);

        // Vérifier l'ajout dans le panier
        $session = $this->client->getContainer()->get('session');
        $cart = $session->get('cart', []);
        $this->assertArrayHasKey($productId . '_' . $size, $cart);

        // Maintenant, effectuer une requête pour supprimer le produit du panier
        $this->client->request('GET', '/cart/remove/'.$productId.'/'.$size);

        // Vérifier si le produit a été retiré du panier
        $cart = $session->get('cart', []);
        $this->assertArrayNotHasKey($productId . '_' . $size, $cart);
    }
}
