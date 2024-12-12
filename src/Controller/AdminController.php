<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Sweatshirt;
use App\Form\SweatshirtType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    // Route pour afficher le dashboard de l'admin
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    // Route pour afficher la liste des sweatshirts
    #[Route('/admin/sweatshirts', name: 'admin_sweatshirt_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $sweatshirts = $entityManager->getRepository(Sweatshirt::class)->findAll();

        return $this->render('admin/sweatshirt/list.html.twig', [
            'sweatshirts' => $sweatshirts,
        ]);
    }
    
    // Route pour créer un nouveau sweatshirt
    #[Route('/admin/sweatshirt/new', name: 'admin_sweatshirt_create', methods: ['POST', 'GET'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sweatshirt = new Sweatshirt();

        if ($request->isMethod('POST')) {
            // Récupérer les données envoyées via POST
            $name = $request->request->get('name');
            $price = (float) $request->request->get('price');
            $sizes = $request->request->get('sizes[]'); // Tableau des tailles
            $quantities = $request->request->get('quantities[]'); // Tableau des quantités

           

            if (is_array($sizes) && is_array($quantities)) {
                // Assurez-vous que les tailles et quantités sont valides
                foreach ($sizes as $size) {
                    if (!in_array($size, ['XS', 'S', 'M', 'L', 'XL'])) {
                        throw new \Exception("Taille invalide: " . $size);
                    }
                }
        
                foreach ($quantities as $quantity) {
                    if (!is_numeric($quantity) || $quantity < 0) {
                        throw new \Exception("Quantité invalide: " . $quantity);
                    }
                }

                // Vérifier que les tailles et quantités sont bien des tableaux
                if (!is_array($sizes) || !is_array($quantities)) {
                    $this->addFlash('error', 'Les tailles et quantités doivent être des tableaux valides.');
                    return $this->redirectToRoute('admin_sweatshirt_create');
                }

                // Vérifier que les tailles et quantités sont bien dans le même nombre
                if (count($sizes) !== count($quantities)) {
                    $this->addFlash('error', 'Le nombre de tailles doit correspondre au nombre de quantités.');
                    return $this->redirectToRoute('admin_sweatshirt_create');
                }

                // Créer un tableau associatif pour les tailles et quantités
               // Créer un tableau associatif pour les tailles et quantités
$sizesQuantities = [];
for ($i = 0; $i < count($sizes); $i++) {
    $size = $sizes[$i];
    $quantity = (int) $quantities[$i]; // Assurez-vous que la quantité est un entier

    // Ajouter directement la taille et sa quantité dans le tableau
    $sizesQuantities[$size] = $quantity;
}



                // Assigner le tableau des tailles et quantités
                $sweatshirt->setSizes($sizesQuantities);

                // Validation et déplacement de l'image téléchargée
                $uploadedFile = $request->files->get('image');
                if ($uploadedFile) {
                    if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                        $newFilename = uniqid() . '.jpg';
                        $uploadedFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                            $newFilename
                        );
                        $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                    } else {
                        $this->addFlash('error', 'Le fichier doit être au format .jpg.');
                        return $this->redirectToRoute('admin_sweatshirt_create');
                    }
                }

                // Créer l'objet Sweatshirt
                $sweatshirt->setName($name);
                $sweatshirt->setPrice($price);

                // Persister l'entité dans la base de données
                $entityManager->persist($sweatshirt);
                $entityManager->flush();

                $this->addFlash('success', 'Produit créé avec succès !');

                return $this->redirectToRoute('admin_sweatshirt_list');
            }
        }

        return $this->render('admin/sweatshirt/new.html.twig');
    }

    #[Route('/admin/sweatshirt/{id}/edit', name: 'admin_sweatshirt_edit', methods: ['POST', 'GET'])]
public function edit(Request $request, Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
{
    if ($request->isMethod('POST')) {
        // Récupérer les données envoyées via POST
        $name = $request->request->get('name');
        $price = (float) $request->request->get('price');
        $featured = $request->request->get('featured') === 'on'; // Checkbox pour featured
        $sizes = $request->request->get('sizes[]'); // Tableau des tailles
        $quantities = $request->request->get('quantities[]'); // Tableau des quantités

        if (is_array($sizes) && is_array($quantities)) {
            // Valider les tailles
            foreach ($sizes as $size) {
                if (!in_array($size, ['XS', 'S', 'M', 'L', 'XL'])) {
                    throw new \Exception("Taille invalide: " . $size);
                }
            }

            // Valider les quantités
            foreach ($quantities as $quantity) {
                if (!is_numeric($quantity) || $quantity < 0) {
                    throw new \Exception("Quantité invalide: " . $quantity);
                }
            }

            // Vérifier la cohérence entre tailles et quantités
            if (count($sizes) !== count($quantities)) {
                $this->addFlash('error', 'Le nombre de tailles doit correspondre au nombre de quantités.');
                return $this->redirectToRoute('admin_sweatshirt_edit', ['id' => $sweatshirt->getId()]);
            }

            // Créer un tableau associatif pour les tailles et quantités
            $sizesQuantities = [];
            for ($i = 0; $i < count($sizes); $i++) {
                $sizesQuantities[$sizes[$i]] = (int) $quantities[$i];
            }

            // Assigner le tableau des tailles et quantités
            $sweatshirt->setSizes($sizesQuantities);

            // Gestion de l'image téléchargée
            $uploadedFile = $request->files->get('image');
            if ($uploadedFile) {
                // Supprimer l'ancienne image si elle existe
                if ($sweatshirt->getImagePath()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $sweatshirt->getImagePath();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Valider le fichier image
                if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                    $newFilename = uniqid() . '.jpg';
                    $uploadedFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $newFilename
                    );

                    $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                } else {
                    $this->addFlash('error', 'Le fichier doit être au format .jpg.');
                    return $this->redirectToRoute('admin_sweatshirt_edit', ['id' => $sweatshirt->getId()]);
                }
            }

            // Mettre à jour les autres propriétés
            $sweatshirt->setName($name);
            $sweatshirt->setPrice($price);
            $sweatshirt->setFeatured($featured);

            // Enregistrer les modifications
            $entityManager->flush();

            $this->addFlash('success', 'Sweatshirt modifié avec succès !');
            return $this->redirectToRoute('admin_sweatshirt_list');
        }
    }

    return $this->render('admin/sweatshirt/edit.html.twig', [
        'sweatshirt' => $sweatshirt,
    ]);
}

    // Route pour supprimer un sweatshirt
    #[Route('/admin/sweatshirt/{id}/delete', name: 'admin_sweatshirt_delete')]
    public function delete(Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
    {
        // Supprimer l'image si elle existe
        $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $sweatshirt->getImagePath();
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Supprimer l'entité de la base de données
        $entityManager->remove($sweatshirt);
        $entityManager->flush();

        $this->addFlash('success', 'Sweatshirt supprimé avec succès !');
        return $this->redirectToRoute('admin_sweatshirt_list');
    }
}
