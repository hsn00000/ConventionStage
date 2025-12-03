<?php

namespace App\Form;

use App\Entity\Organisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('addressHq')
            ->add('postalCodeHq')
            ->add('cityHq')
            ->add('addressInternship')
            ->add('postalCodeInternship')
            ->add('cityInternship')
            ->add('website')
            ->add('respName')
            ->add('respFunction')
            ->add('respEmail')
            ->add('respPhone')
            ->add('insuranceName')
            ->add('insuranceContract')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,
        ]);
    }
}
