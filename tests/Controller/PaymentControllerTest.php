<?php

// tests/Controller/PaymentControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testPaymentPage()
    {
        $client = static::createClient();
        $client->request('GET', '/payment');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Paiement'); // Vérifiez que la page contient un certain texte, ou vérifiez le rendu de la page
    }

    public function testCreateCheckoutSession()
    {
        $client = static::createClient();
        
        // Simulez un panier dans la session
        $client->getContainer()->get('session')->set('cart', [
            ['name' => 'Produit A', 'price' => 100, 'quantity' => 1],
            ['name' => 'Produit B', 'price' => 200, 'quantity' => 2],
        ]);
        
        $client->getContainer()->get('session')->set('checkout_total', 500);
        
        $client->request('GET', '/create-checkout-session');
        
        // Vérifier si l'utilisateur est redirigé vers Stripe
        $this->assertResponseRedirects('http://127.0.0.1:8000/payment/success');
    }

    public function testPaymentSuccessPage()
    {
        $client = static::createClient();
        $client->request('GET', '/payment/success');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Paiement réussi');
    }

    public function testPaymentCancelPage()
    {
        $client = static::createClient();
        $client->request('GET', '/payment/cancel');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Paiement annulé');
    }
}
