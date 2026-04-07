<?php

namespace App\Form;

use App\Entity\Exercise;
use App\Entity\Program;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProgramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du programme',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: Programme Perte de Poids 8 semaines'],
            ])
            ->add('goal', TextType::class, [
                'label' => 'Objectif',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'Ex: Perte de poids, Prise de masse...'],
            ])
            ->add('durationWeeks', IntegerType::class, [
                'label' => 'Durée (semaines)',
                'attr'  => ['class' => 'form-control', 'min' => 1, 'max' => 52],
            ])
            ->add('level', ChoiceType::class, [
                'label'   => 'Niveau',
                'choices' => [
                    'Débutant'      => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé'        => 'Avancé',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('exercises', EntityType::class, [
                'class'        => Exercise::class,
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => false,
                'required'     => false,
                'label'        => 'Exercices associés',
                'attr'         => ['class' => 'form-select', 'size' => 8, 'style' => 'height: auto'],
                'help'         => 'Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs exercices.',
            ])
            ->add('isPublished', CheckboxType::class, [
                'label'    => 'Publier ce programme (visible sur le site public)',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Program::class,
        ]);
    }
}
