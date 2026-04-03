<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InitiateContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise (pour référence)',
                'attr' => ['placeholder' => 'Ex: Capgemini, Mairie de...'],
                'constraints' => [new NotBlank(['message' => 'Veuillez indiquer le nom de l\'entreprise.'])],
            ])
            ->add('tutorEmail', EmailType::class, [
                'label' => 'Email du responsable / tuteur en entreprise',
                'attr' => ['placeholder' => 'tuteur@entreprise.com'],
                'help' => 'C\'est à cette adresse que nous enverrons le lien pour remplir la convention.',
                'constraints' => [new NotBlank(['message' => 'L\'email est obligatoire pour envoyer la demande.'])],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
