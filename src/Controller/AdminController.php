<?php

namespace App\Controller;

use App\Entity\Sweatshirt;
use App\Repository\SweatshirtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/sweatshirts', name: 'admin_sweatshirt_list')]
    public function list(SweatshirtRepository $repository): Response
    {
        $sweatshirts = $repository->findAll();
        
        return $this->render('admin/sweatshirt/list.html.twig', [
            'products' => $sweatshirts
        ]);
    }

    #[Route('/admin/sweatshirt/create', name: 'admin_sweatshirt_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Si c'est une requête POST, on traite le formulaire
        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $name = $request->request->get('name');
            $price = $request->request->get('price');
            
            // Récupérer les quantités
            $sizes = [
                'XS' => (int)$request->request->get('stock_xs'),
                'S' => (int)$request->request->get('stock_s'),
                'M' => (int)$request->request->get('stock_m'),
                'L' => (int)$request->request->get('stock_l'),
                'XL' => (int)$request->request->get('stock_xl')
            ];

            // Créer le nouveau sweatshirt
            $sweatshirt = new Sweatshirt();
            $sweatshirt->setName($name);
            $sweatshirt->setPrice($price);
            $sweatshirt->setSizes($sizes);
            $sweatshirt->setFeatured(false);

            // Gérer l'upload de l'image
            $image = $request->files->get('image');
            if ($image) {
                $fileName = uniqid().'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fileName
                );
                $sweatshirt->setImagePath('uploads/'.$fileName);
            }

            $entityManager->persist($sweatshirt);
            $entityManager->flush();

            $stockMessage = sprintf(
                "Produit créé : %s - Prix : %.2f€ - Stocks : XS:%d S:%d M:%d L:%d XL:%d",
                $name,
                $price,
                $sizes['XS'],
                $sizes['S'],
                $sizes['M'],
                $sizes['L'],
                $sizes['XL']
            );
            
            $this->addFlash('success', $stockMessage);
            return $this->redirectToRoute('admin_sweatshirt_list');
        }

        // Si c'est une requête GET, on affiche le formulaire
        return $this->render('admin/sweatshirt/create.html.twig');
    }

    #[Route('/admin/sweatshirt/edit/{id}', name: 'admin_sweatshirt_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, SweatshirtRepository $repository): Response
    {
        $product = $repository->find($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        if ($request->isMethod('POST')) {
            // Mise à jour des données
            $product->setName($request->request->get('name'));
            $product->setPrice($request->request->get('price'));
            
            $sizes = [
                'XS' => (int)$request->request->get('stock_xs'),
                'S' => (int)$request->request->get('stock_s'),
                'M' => (int)$request->request->get('stock_m'),
                'L' => (int)$request->request->get('stock_l'),
                'XL' => (int)$request->request->get('stock_xl')
            ];
            $product->setSizes($sizes);

            // Gestion de l'image
            $image = $request->files->get('image');
            if ($image) {
                $fileName = uniqid().'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fileName
                );
                $product->setImagePath('uploads/'.$fileName);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié avec succès');
            return $this->redirectToRoute('admin_sweatshirt_list');
        }

        return $this->render('admin/sweatshirt/edit.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/admin/sweatshirt/delete/{id}', name: 'admin_sweatshirt_delete')]
    public function delete(int $id, EntityManagerInterface $entityManager, SweatshirtRepository $repository): Response
    {
        $product = $repository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        $entityManager->remove($product);
        $entityManager->flush();
        return $this->redirectToRoute('admin_sweatshirt_list');
    }
}
