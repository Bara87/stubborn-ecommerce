<?php

namespace App\Form;

use App\Entity\Sweatshirt;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SweatshirtType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ pour le nom du sweatshirt
            ->add('name', TextType::class, [
                'label' => 'Nom du Sweatshirt',
                'attr' => ['placeholder' => 'Nom du sweatshirt']
            ])
            // Champ pour le prix du sweatshirt
            ->add('price', NumberType::class, [
                'label' => 'Prix du Sweatshirt',
                'attr' => ['placeholder' => 'Prix en €']
            ])
            // Champ pour la taille du sweatshirt
            ->add('size', TextType::class, [
                'label' => 'Taille',
                'attr' => ['placeholder' => 'Taille (ex. M, L, XL)']
            ])
            // Champ pour le stock disponible
            ->add('stock', NumberType::class, [
                'label' => 'Stock',
                'attr' => ['placeholder' => 'Quantité en stock']
            ])
            // Champ pour savoir si le produit est mis en avant
            ->add('featured', CheckboxType::class, [
                'label' => 'Produit en vedette',
                'required' => false, // Si c'est optionnel
            ])
            // Champ pour télécharger l'image du sweatshirt
            ->add('image', FileType::class, [
                'mapped' => false, // Ce champ n'est pas lié à une propriété de l'entité
                'required' => false, // L'image est optionnelle
                'label' => 'Image du produit',
                'attr' => ['accept' => 'image/*'] // Limiter aux fichiers image
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sweatshirt::class, // Associe ce formulaire à l'entité Sweatshirt
        ]);
    }
}
