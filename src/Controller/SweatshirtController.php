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

class SweatshirtController extends AbstractController
{
    #[Route('/sweatshirt/new', name: 'app_sweatshirt_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sweatshirt = new Sweatshirt();
        $form = $this->createForm(SweatshirtType::class, $sweatshirt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('image')->getData();

            if ($uploadedFile) {
                // Générer un nom unique pour le fichier
                $newFilename = uniqid() . '.' . $uploadedFile->guessExtension();

                // Déplacer le fichier vers le dossier public/uploads/images
                $uploadedFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                    $newFilename
                );

                // Enregistrer le chemin relatif dans l'entité
                $sweatshirt->setImagePath('/uploads/images/' . $newFilename);
            }

            $entityManager->persist($sweatshirt);
            $entityManager->flush();

            $this->addFlash('success', 'Sweatshirt créé avec succès !');

            return $this->redirectToRoute('app_sweatshirt_list'); // Redirige vers la liste des produits
        }

        return $this->render('sweatshirt/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
