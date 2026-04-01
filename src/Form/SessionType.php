<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Session;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la campagne',
            ])
            ->add('level', EntityType::class, [
                'class' => Level::class,
                'choice_label' => 'levelName',
                'label' => 'Classe',
                'placeholder' => 'Choisir une classe',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Campagne active',
                'required' => false,
            ])
            ->add('sessionDates', CollectionType::class, [
                'entry_type' => SessionDateType::class,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__period__',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}
