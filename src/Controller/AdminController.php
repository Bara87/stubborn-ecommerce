<?php

namespace App\Controller;

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
    // Route pour afficher la liste des sweatshirts
    #[Route('/admin/sweatshirts', name: 'admin_sweatshirt_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $sweatshirts = $entityManager->getRepository(Sweatshirt::class)->findAll();

        return $this->render('admin/sweatshirt/list.html.twig', [
            'sweatshirts' => $sweatshirts,
        ]);
    }

    // Route pour ajouter un nouveau sweatshirt
    #[Route('/admin/sweatshirt/new', name: 'admin_sweatshirt_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sweatshirt = new Sweatshirt();
        $form = $this->createForm(SweatshirtType::class, $sweatshirt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('image')->getData();

            if ($uploadedFile) {
                // Validation du fichier image
                $allowedMimeTypes = ['image/jpeg','image/jpeg', 'image/png', 'image/gif'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($uploadedFile->getMimeType(), $allowedMimeTypes) && in_array($uploadedFile->guessExtension(), $allowedExtensions)) {
                    $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
                    $uploadedFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );

                    $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                } else {
                    $this->addFlash('error', 'Le fichier n\'est pas valide. Seules les images (jpg, png, gif) sont autorisées.');
                    return $this->redirectToRoute('admin_sweatshirt_new');
                }
            }

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

                // Validation du fichier image
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($uploadedFile->getMimeType(), $allowedMimeTypes) && in_array($uploadedFile->guessExtension(), $allowedExtensions)) {
                    $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();
                    $uploadedFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );

                    $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
                } else {
                    $this->addFlash('error', 'Le fichier n\'est pas valide. Seules les images (jpg, png, gif) sont autorisées.');
                    return $this->redirectToRoute('admin_sweatshirt_edit', ['id' => $sweatshirt->getId()]);
                }
            }

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
