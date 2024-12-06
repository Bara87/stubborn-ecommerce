<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;


class SweatshirtType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        // Champs pour le nom, le prix, etc.
        ->add('name', TextType::class, [
            'label' => 'Nom du Sweatshirt',
            'attr' => ['placeholder' => 'Nom du sweatshirt']
        ])
        ->add('price', NumberType::class, [
            'label' => 'Prix du Sweatshirt',
            'attr' => ['placeholder' => 'Prix en €']
        ])
        // Champ pour les tailles et quantités
        ->add('sizes', CollectionType::class, [
            'entry_type' => TextType::class, // Vous pouvez également utiliser un autre type si nécessaire pour la quantité
            'entry_options' => [
                'label' => false,
                'attr' => ['placeholder' => 'Taille']
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false, 
            'label' => 'Tailles et Quantités',
            'attr' => ['class' => 'size-quantity-collection'],
        ])
        ->add('featured', CheckboxType::class, [
            'label' => 'Produit en vedette',
            'required' => false,
        ])
        ->add('image', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Image du produit',
            'attr' => ['accept' => 'image/*']
        ]);
}
}
