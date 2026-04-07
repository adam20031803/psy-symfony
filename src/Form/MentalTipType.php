<?php

namespace App\Form;

use App\Entity\MentalTip;
use App\Entity\Mood;
use App\Repository\MoodRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MentalTipType extends AbstractType
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
            ->add('tipText', TextareaType::class, [
                'label' => 'Tip',
                'required' => true,
                'attr' => ['rows' => 5, 'maxlength' => 255, 'minlength' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MentalTip::class,
        ]);
    }
}
