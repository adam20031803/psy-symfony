<?php

namespace App\Form;

use App\Entity\Habitude;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HabitudeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Santé' => 'Santé',
                    'Mental' => 'Mental',
                    'Productivité' => 'Productivité',
                    'Finance' => 'Finance',
                    'Social' => 'Social',
                    'Créativité' => 'Créativité',
                    'Carrière' => 'Carrière',
                    'Spiritualité' => 'Spiritualité',
                ],
                'placeholder' => 'Sélectionnez une catégorie',
            ])
            ->add('frequencyType', IntegerType::class, [
                'label' => 'Frequency (times per week)',
                'data' => 7,
                'attr' => [
                    'min' => 1,
                    'max' => 7,
                    'class' => 'form-control',
                ],
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date'
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'End Date'
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => 'Active'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Habitude::class,
        ]);
    }
}
