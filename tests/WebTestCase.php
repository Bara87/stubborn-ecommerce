<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use App\Entity\User;
use App\Entity\Sweatshirt;

abstract class WebTestCase extends SymfonyWebTestCase
{
    protected $client;
    protected $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        
        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    protected function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setPassword('password123');
        $user->setName('Test User');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createTestProduct(): Sweatshirt
    {
        $product = new Sweatshirt();
        $product->setName('Test Sweatshirt');
        $product->setPrice(29.90);
        $product->setSizes(['M' => 10, 'L' => 5]);
        $product->setImagePath('test.jpg');
        $product->setFeatured(false);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    protected function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Sweatshirt')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->client = null;
    }
} 