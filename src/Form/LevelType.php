<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Professor; // Import important
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // Import important
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LevelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('levelCode', TextType::class, [
                'label' => 'Code (ex: BTS SIO 1)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('levelName', TextType::class, [
                'label' => 'Libellé complet',
                'attr' => ['class' => 'form-control']
            ])
            // AJOUT : Sélection du Professeur Référent
            ->add('mainProfessor', EntityType::class, [
                'class' => Professor::class,
                'label' => 'Professeur Référent de la classe',
                'placeholder' => 'Sélectionner un professeur',
                'choice_label' => function (Professor $prof) {
                    return $prof->getLastname() . ' ' . $prof->getFirstname();
                },
                'attr' => ['class' => 'form-select'],
                'required' => false // Optionnel au début
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
