<?php

namespace App\Form;

use App\Entity\DailyCheckin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DailyCheckinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rangeOpts = [
            'attr' => [
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'class' => 'checkin-range',
            ],
        ];

        $builder
            ->add('checkinDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
            ])
            ->add('moodRating', RangeType::class, array_merge($rangeOpts, [
                'label' => 'Humeur (1–10)',
            ]))
            ->add('energyLevel', RangeType::class, array_merge($rangeOpts, [
                'label' => 'Énergie (1–10)',
            ]))
            ->add('productivityLevel', RangeType::class, array_merge($rangeOpts, [
                'label' => 'Productivité (1–10)',
            ]))
            ->add('stressLevel', RangeType::class, array_merge($rangeOpts, [
                'label' => 'Stress (1–10)',
            ]))
            ->add('sleepQuality', RangeType::class, array_merge($rangeOpts, [
                'label' => 'Qualité du sommeil (1–10)',
            ]))
            ->add('sleepHours', NumberType::class, [
                'scale' => 2,
                'attr' => [
                    'min' => 0,
                    'max' => 24,
                    'step' => 0.25,
                    'class' => 'form-control',
                ],
                'label' => 'Heures de sommeil',
            ])
            ->add('whatWentWell', TextareaType::class, [
                'required' => false,
                'label' => 'Qu\'est-ce qui s\'est bien passé ?',
                'attr' => ['rows' => 4, 'class' => 'form-control'],
            ])
            ->add('whatCouldImprove', TextareaType::class, [
                'required' => false,
                'label' => 'Qu\'est-ce qui pourrait s\'améliorer ?',
                'attr' => ['rows' => 4, 'class' => 'form-control'],
            ])
            ->add('gratitude', TextareaType::class, [
                'required' => false,
                'label' => 'Gratitude',
                'attr' => ['rows' => 3, 'class' => 'form-control'],
            ])
            ->add('mainChallenges', TextareaType::class, [
                'required' => false,
                'label' => 'Principaux défis',
                'attr' => ['rows' => 3, 'class' => 'form-control'],
            ])
            ->add('biggestWins', TextareaType::class, [
                'required' => false,
                'label' => 'Plus grandes victoires',
                'attr' => ['rows' => 3, 'class' => 'form-control'],
            ])
            ->add('additionalNotes', TextareaType::class, [
                'required' => false,
                'label' => 'Notes additionnelles',
                'attr' => ['rows' => 3, 'class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DailyCheckin::class,
        ]);
    }
}
