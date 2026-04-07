<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => "Nom de l'exercice",
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: Squat, Pompes...'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Décrivez cet exercice...'],
            ])
            ->add('category', ChoiceType::class, [
                'label'   => 'Catégorie',
                'choices' => [
                    'Cardio'       => 'Cardio',
                    'Musculation'  => 'Musculation',
                    'Yoga'         => 'Yoga',
                    'HIIT'         => 'HIIT',
                    'Flexibilité'  => 'Flexibilité',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('difficulty', ChoiceType::class, [
                'label'   => 'Difficulté',
                'choices' => [
                    'Débutant'      => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé'        => 'Avancé',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr'  => ['class' => 'form-control', 'min' => 1, 'placeholder' => 'Ex: 30'],
            ])
            ->add('calories', NumberType::class, [
                'label' => 'Calories brûlées',
                'attr'  => ['class' => 'form-control', 'min' => 0, 'step' => '0.1', 'placeholder' => 'Ex: 250'],
            ])
            ->add('imageUrl', UrlType::class, [
                'label'    => "URL de l'image",
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'https://...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}
