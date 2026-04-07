<?php

namespace App\Form;

use App\Entity\MentalEntry;
use App\Entity\Mood;
use App\Repository\MoodRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MentalEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mood', EntityType::class, [
                'class' => Mood::class,
                'choice_label' => 'moodName',
                'label' => 'Mood (référence)',
                'placeholder' => 'Choisir…',
                'required' => true,
                'invalid_message' => 'La valeur du mood (identifiant / choix) n’est pas valide.',
                'query_builder' => fn (MoodRepository $r) => $r->createQueryBuilder('m')->orderBy('m.moodName', 'ASC'),
            ])
            ->add('entryDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'attr' => [
                    'max' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
                ],
            ])
            ->add('emotionLevel', IntegerType::class, [
                'label' => 'Niveau d’émotion',
                'required' => true,
                'attr' => ['min' => 1, 'max' => 10, 'inputmode' => 'numeric'],
            ])
            ->add('activity', TextType::class, [
                'label' => 'Activity',
                'required' => true,
                'attr' => ['maxlength' => 120, 'minlength' => 2],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => ['rows' => 4, 'maxlength' => 255],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MentalEntry::class,
        ]);
    }
}
