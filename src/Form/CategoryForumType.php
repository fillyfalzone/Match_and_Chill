<?php

namespace App\Form;

use App\Entity\CategoryForum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CategoryForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('Submit', SubmitType::class, [
                'attr' => [ 'class' => 'submit-btn']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryForum::class,
            // Activation de la protection CSRF
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            // Génération d'un identifiant unique pour le token lié à un type de formulire spécifique
            'csrf_token_id' => 'registration_item',
        ]);
    }
}
