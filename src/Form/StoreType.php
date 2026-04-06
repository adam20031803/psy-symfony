<?php

namespace App\Form;

use App\Entity\Store;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Store Name'
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Address'
            ])
            ->add('category', TextType::class, [
                'label' => 'Category'
            ])
            ->add('latitude', NumberType::class, [
                'scale' => 6,
                'label' => 'Latitude'
            ])
            ->add('longitude', NumberType::class, [
                'scale' => 6,
                'label' => 'Longitude'
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'label' => 'Phone Number'
            ])
            ->add('website', UrlType::class, [
                'required' => false,
                'label' => 'Website URL'
            ])
            ->add('openingHours', TextareaType::class, [
                'required' => false,
                'label' => 'Opening Hours'
            ])
            ->add('rating', RangeType::class, [
                'attr' => ['min' => 0, 'max' => 5, 'step' => 0.1],
                'label' => 'Rating (0-5)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
