<?php

namespace App\Form;

use App\Entity\Parameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('provisorName', TextType::class, [
                'label' => 'Nom du proviseur',
            ])
            ->add('provisorEmail', EmailType::class, [
                'label' => 'Email du proviseur',
            ])
            ->add('ddfptName', TextType::class, [
                'label' => 'Nom de la DDFPT',
            ])
            ->add('ddfptPhone', TextType::class, [
                'label' => 'Téléphone de la DDFPT',
            ])
            ->add('ddfptEmail', EmailType::class, [
                'label' => 'Email de la DDFPT',
            ])
            ->add('yousignApprover', CheckboxType::class, [
                'label' => 'M\'ajouter comme approbatrice YouSign avant envoi des signatures',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parameters::class,
        ]);
    }
}
