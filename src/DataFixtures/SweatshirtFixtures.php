<?php

namespace App\DataFixtures;

use App\Entity\Sweatshirt;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SweatshirtFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste des produits avec tailles, prix et autres attributs
        $products = [
            [
                'name' => 'Blackbelt',
                'price' => 29.90,
                'featured' => true,
                'sizes' => [
                    'XS' => 5,
                    'S'  => 10,
                    'M'  => 8,
                    'L'  => 6,
                    'XL' => 4,
                ],
                'imagePath' => '/uploads/images/blackbelt.jpg',
            ],
            [
                'name' => 'BlueBelt',
                'price' => 29.90,
                'featured' => false,
                'sizes' => [
                    'XS' => 3,
                    'S'  => 5,
                    'M'  => 7,
                    'L'  => 9,
                    'XL' => 2,
                ],
                'imagePath' => '/uploads/images/bluebelt.jpg',
            ],
            [
                'name' => 'Street',
                'price' => 34.50,
                'featured' => false,
                'sizes' => [
                    'XS' => 2,
                    'S'  => 4,
                    'M'  => 6,
                    'L'  => 8,
                    'XL' => 3,
                ],
                'imagePath' => '/uploads/images/street.jpg',
            ],
            [
                'name' => 'Pokeball',
                'price' => 45.00,
                'featured' => true,
                'sizes' => [
                    'XS' => 4,
                    'S'  => 8,
                    'M'  => 6,
                    'L'  => 5,
                    'XL' => 3,
                ],
                'imagePath' => '/uploads/images/pokeball.jpg',
            ],
            [
                'name' => 'PinkLady',
                'price' => 29.90,
                'featured' => false,
                'sizes' => [
                    'XS' => 6,
                    'S'  => 7,
                    'M'  => 5,
                    'L'  => 3,
                    'XL' => 2,
                ],
                'imagePath' => '/uploads/images/pinklady.jpg',
            ],
            [
                'name' => 'Snow',
                'price' => 32.00,
                'featured' => false,
                'sizes' => [
                    'XS' => 3,
                    'S'  => 6,
                    'M'  => 7,
                    'L'  => 4,
                    'XL' => 2,
                ],
                'imagePath' => '/uploads/images/snow.jpg',
            ],
            [
                'name' => 'Greyback',
                'price' => 28.50,
                'featured' => false,
                'sizes' => [
                    'XS' => 4,
                    'S'  => 3,
                    'M'  => 5,
                    'L'  => 7,
                    'XL' => 6,
                ],
                'imagePath' => '/uploads/images/greyback.jpg',
            ],
            [
                'name' => 'BlueCloud',
                'price' => 45.00,
                'featured' => false,
                'sizes' => [
                    'XS' => 2,
                    'S'  => 5,
                    'M'  => 3,
                    'L'  => 6,
                    'XL' => 4,
                ],
                'imagePath' => '/uploads/images/bluecloud.jpg',
            ],
            [
                'name' => 'BornInUsa',
                'price' => 59.90,
                'featured' => true,
                'sizes' => [
                    'XS' => 3,
                    'S'  => 6,
                    'M'  => 4,
                    'L'  => 5,
                    'XL' => 3,
                ],
                'imagePath' => '/uploads/images/borninusa.jpg',
            ],
            [
                'name' => 'GreenSchool',
                'price' => 42.20,
                'featured' => false,
                'sizes' => [
                    'XS' => 4,
                    'S'  => 3,
                    'M'  => 6,
                    'L'  => 2,
                    'XL' => 5,
                ],
                'imagePath' => '/uploads/images/greenschool.jpg',
            ],
        ];

        foreach ($products as $productData) {
            $product = new Sweatshirt();
            $product->setName($productData['name']);
            $product->setPrice($productData['price']);
            $product->setFeatured($productData['featured']);
            $product->setSizes($productData['sizes']);
            $product->setImagePath($productData['imagePath']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
