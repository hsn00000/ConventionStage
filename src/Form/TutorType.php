<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Tutor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TutorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('telMobile')
            ->add('telOther')
            ->add('sessions', EntityType::class, [
                'class' => Session::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tutor::class,
        ]);
    }
}
