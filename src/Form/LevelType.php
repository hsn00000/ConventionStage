<?php

namespace App\Form;

use App\Entity\Level;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LevelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('levelCode', TextType::class, [
                'label' => 'Code de la formation',
                'attr' => ['placeholder' => 'Ex: STS SIO 1'],
                'constraints' => [new NotBlank(['message' => 'Le code est obligatoire'])]
            ])
            ->add('levelName', TextType::class, [
                'label' => 'Libellé de la formation',
                'attr' => ['placeholder' => 'Ex: Services Informatiques aux Organisations 1ère année'],
                'constraints' => [new NotBlank(['message' => 'Le libellé est obligatoire'])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Level::class,
        ]);
    }
}
