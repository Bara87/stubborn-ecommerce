<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Sweatshirt;
use App\Form\SweatshirtType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{    
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

    // src/Controller/AdminController.php

    #[Route('/admin/sweatshirt/new', name: 'admin_sweatshirt_new')]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $sweatshirt = new Sweatshirt();

    // Initialisation du tableau 'sizes' si nécessaire
    $sweatshirt->setSizes(['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0]);

    $form = $this->createForm(SweatshirtType::class, $sweatshirt);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Récupérer les tailles et quantités depuis le formulaire
        $sizes = $form->get('sizes')->getData(); // C'est un tableau associatif (taille => quantité)
        $sweatshirt->setSizes($sizes);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('image')->getData();

        if ($uploadedFile) {
            // Vérification si l'extension du fichier est .jpg
            if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                // Déplacer l'image
                $newFilename = uniqid() . '.jpg'; // Forcer l'extension .jpg
                $uploadedFile->move(
                    $this->getParameter('upload_directory'),
                    $newFilename
                );

                $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
            } else {
                $this->addFlash('error', 'Le fichier doit être au format .jpg.');
                return $this->redirectToRoute('admin_sweatshirt_new');
            }
        }

        // Persist the entity to the database
        $entityManager->persist($sweatshirt);
        $entityManager->flush();

        $this->addFlash('success', 'Sweatshirt créé avec succès !');
        return $this->redirectToRoute('admin_sweatshirt_list');
    }

    return $this->render('admin/sweatshirt/new.html.twig', [
        'form' => $form->createView(),
    ]);
}

    // Route pour modifier un sweatshirt existant
    #[Route('/admin/sweatshirt/{id}/edit', name: 'admin_sweatshirt_edit')]
public function edit(Request $request, Sweatshirt $sweatshirt, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(SweatshirtType::class, $sweatshirt);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('image')->getData();

        if ($uploadedFile) {
            // Supprimer l'ancienne image si elle existe
            if ($sweatshirt->getImagePath()) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $sweatshirt->getImagePath();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Validation du fichier image (seulement JPG dans ce cas)
            if ($uploadedFile->guessExtension() === 'jpg' || $uploadedFile->guessExtension() === 'jpeg') {
                $newFilename = uniqid() . '.jpg'; // Forcer l'extension .jpg
                $uploadedFile->move(
                    $this->getParameter('upload_directory'),
                    $newFilename
                );

                $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
            } else {
                $this->addFlash('error', 'Le fichier doit être au format .jpg.');
                return $this->redirectToRoute('admin_sweatshirt_edit', ['id' => $sweatshirt->getId()]);
            }
        }

        // Sauvegarder les données
        $entityManager->flush();

        $this->addFlash('success', 'Sweatshirt modifié avec succès !');
        return $this->redirectToRoute('admin_sweatshirt_list');
    }

    return $this->render('admin/sweatshirt/edit.html.twig', [
        'form' => $form->createView(),
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

        $entityManager->remove($sweatshirt);
        $entityManager->flush();

        $this->addFlash('success', 'Sweatshirt supprimé avec succès !');
        return $this->redirectToRoute('admin_sweatshirt_list');
    }
}
