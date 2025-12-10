<?php

namespace App\Form;

use App\Entity\Contract;
use App\Form\OrganisationType;
use App\Form\TutorType;
use App\Form\WeeklyScheduleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyFillContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- 1. ORGANISATION (Sous-formulaire) ---
            ->add('organisation', OrganisationType::class, [
                'label' => false,
            ])

            // --- 2. TUTEUR (Sous-formulaire) ---
            ->add('tutor', ContractTutorType::class, [
                'label' => false,
            ])

            // --- 3. QUESTIONNAIRE LOGISTIQUE ---
            ->add('deplacement', CheckboxType::class, [
                'label' => 'L\'étudiant sera-t-il amené à effectuer des déplacements itinérants ?',
                'required' => false,
            ])
            ->add('transportFreeTaken', CheckboxType::class, [
                'label' => 'L\'entreprise prend-elle en charge les frais de transport ?',
                'required' => false,
            ])
            ->add('lunchTaken', CheckboxType::class, [
                'label' => 'L\'entreprise prend-elle en charge les frais de restauration ?',
                'required' => false,
            ])
            ->add('hostTaken', CheckboxType::class, [
                'label' => 'L\'entreprise prend-elle en charge les frais d\'hébergement ?',
                'required' => false,
            ])
            ->add('bonus', CheckboxType::class, [
                'label' => 'Une gratification est-elle prévue ?',
                'required' => false,
            ])
            ->add('bonusAmount', MoneyType::class, [
                'label' => 'Montant mensuel net',
                'required' => false, // Important : false car on ne le remplit que si bonus est coché
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Ex: 600.00'
                ]
            ])

            // --- 4. HORAIRES ---
            ->add('workHours', WeeklyScheduleType::class, [
                'label' => false,
                'required' => false,
            ])

            // --- 5. PÉDAGOGIE ---
            ->add('plannedActivities', TextareaType::class, [
                'label' => 'Activités prévues et compétences à développer',
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Décrivez ici les missions principales qui seront confiées au stagiaire...'
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'cascade_validation' => true,
        ]);
    }
}
