<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Tutor;
use App\Form\WeeklyScheduleType; // <--- IMPORTANT : Importez votre nouveau type
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organisation', EntityType::class, [
                'class' => Organisation::class,
                'choice_label' => 'name',
                'label' => 'Entreprise d\'accueil',
                'placeholder' => 'Sélectionnez une entreprise',
                'required' => true,
            ])
            ->add('tutor', EntityType::class, [
                'class' => Tutor::class,
                'choice_label' => function (Tutor $tutor) {
                    return $tutor->getFirstname() . ' ' . $tutor->getLastname();
                },
                'label' => 'Tuteur de stage',
                'placeholder' => 'Sélectionnez un tuteur',
                'required' => true,
            ])
            ->add('plannedActivities', TextareaType::class, [
                'label' => 'Activités prévues (Sujet du stage)',
                'attr' => ['rows' => 5],
            ])

            // --- CORRECTION ICI : Utilisation de WeeklyScheduleType ---
            ->add('workHours', WeeklyScheduleType::class, [
                'label' => false, // On gère l'affichage manuellement dans Twig
                'required' => false,
            ])
            // ----------------------------------------------------------

            ->add('deplacement', CheckboxType::class, [
                'label' => 'Des déplacements sont-ils prévus ?',
                'required' => false,
            ])
            ->add('transportFreeTaken', CheckboxType::class, [
                'label' => 'Prise en charge des frais de transport ?',
                'required' => false,
            ])
            ->add('lunchTaken', CheckboxType::class, [
                'label' => 'Prise en charge des repas ?',
                'required' => false,
            ])
            ->add('hostTaken', CheckboxType::class, [
                'label' => 'Hébergement fourni ?',
                'required' => false,
            ])
            ->add('bonus', CheckboxType::class, [
                'label' => 'Une gratification est-elle prévue ?',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
        ]);
    }
}
