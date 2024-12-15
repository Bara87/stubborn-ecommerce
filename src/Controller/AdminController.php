<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Sweatshirt;
use App\Repository\SweatshirtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    // Route pour promouvoir un utilisateur au rôle administrateur
    #[Route('/promote-user/{userId}', name: 'promote_user', methods: ['GET'])]
    public function promoteUser(int $userId, EntityManagerInterface $entityManager): Response
    {
        // Trouver l'utilisateur par son ID
        $user = $entityManager->getRepository(User::class)->find($userId);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        // Ajouter le rôle ROLE_ADMIN
        $user->setRoles(array_merge($user->getRoles(), ['ROLE_ADMIN']));

        // Sauvegarder les changements
        $entityManager->flush();

        // Retourner une réponse
        return $this->json([
            'message' => 'Utilisateur promu en administrateur avec succès !',
        ]);
    }

    // Route pour afficher la liste des sweatshirts
    #[Route('/admin/sweatshirt', name: 'admin_sweatshirt_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $sweatshirts = $entityManager->getRepository(Sweatshirt::class)->findAll();

        return $this->render('admin/sweatshirt/index.html.twig', [
            'products' => $sweatshirts,
        ]);
    }

    // Route pour la création d'un sweatshirt
    #[Route('/admin/sweatshirt/create', name: 'admin_sweatshirt_create', methods: ['POST', 'GET'])]
    public function create(Request $request, EntityManagerInterface $entityManager, SweatshirtRepository $sweatshirtRepository): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $price = (float) $request->request->get('price');
            $featured = $request->request->get('featured') === 'on';

            // Récupération des tailles et des stocks (comme un tableau associatif)
            $sizes = [
                'XS' => (int) $request->request->get('stock_xs', 0),
                'S' => (int) $request->request->get('stock_s', 0),
                'M' => (int) $request->request->get('stock_m', 0),
                'L' => (int) $request->request->get('stock_l', 0),
                'XL' => (int) $request->request->get('stock_xl', 0),
            ];

            // Création du produit
            $sweatshirt = new Sweatshirt();
            $sweatshirt->setName($name);
            $sweatshirt->setPrice($price);
            $sweatshirt->setFeatured($featured);
            $sweatshirt->setSizes($sizes);  // Affectation du tableau des tailles

            // Gestion de l'image
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                    $newFilename = uniqid() . '.jpg';
                    $uploadedFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $newFilename
                    );
                    $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                }
            }

            // Sauvegarde en base de données
            $entityManager->persist($sweatshirt);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('admin_sweatshirt_list');
        }
        
        // Récupération des produits mis en avant (max 3)
        $featuredSweatshirts = $sweatshirtRepository->findBy(
            ['featured' => true],
            null,
            3
        );

        return $this->render('admin/sweatshirt/backoffice.html.twig', [
            'featuredSweatshirts' => $featuredSweatshirts,  // Passer les produits mis en avant
        ]);
    }


    // Route pour la modification d'un sweatshirt
    #[Route('/admin/sweatshirt/{id}/edit', name: 'admin_sweatshirt_edit', methods: ['POST', 'GET'])]
    public function edit(Request $request, Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $price = (float) $request->request->get('price');
            $featured = $request->request->get('featured') === 'on';

            // Récupération des tailles et des stocks (comme un tableau associatif)
            $sizes = [
                'XS' => (int) $request->request->get('stock_xs', 0),
                'S' => (int) $request->request->get('stock_s', 0),
                'M' => (int) $request->request->get('stock_m', 0),
                'L' => (int) $request->request->get('stock_l', 0),
                'XL' => (int) $request->request->get('stock_xl', 0),
            ];

            // Affectation des nouvelles données
            $sweatshirt->setName($name);
            $sweatshirt->setPrice($price);
            $sweatshirt->setFeatured($featured);
            $sweatshirt->setSizes($sizes); // Mise à jour du tableau des tailles

            // Gestion de l'image
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                    $newFilename = uniqid() . '.jpg';
                    $uploadedFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $newFilename
                    );
                    $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                }
            }

            // Sauvegarde des modifications
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour avec succès !');
            return $this->redirectToRoute('admin_sweatshirt_list');
        }

        return $this->render('admin/sweatshirt/edit.html.twig', [
            'product' => $sweatshirt,
        ]);
    }

    // Route pour la suppression d'un sweatshirt
    #[Route('/admin/sweatshirt/{id}/delete', name: 'admin_sweatshirt_delete', methods: ['POST'])]
    public function delete(Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
    {
 
        // Suppression du produit
        $entityManager->remove($sweatshirt);
        $entityManager->flush();
        $this->addFlash('success', 'Produit supprimé avec succès !');

        return $this->redirectToRoute('admin_sweatshirt_list');
    }
}
