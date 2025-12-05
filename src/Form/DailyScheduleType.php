<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DailyScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Configuration simple : juste le sélecteur d'heure, sans restrictions
        $timeOptions = [
            'widget' => 'single_text', // Affiche l'input type="time" du navigateur
            'required' => false,       // Autorise les champs vides
            'label' => false,          // Pas de label (géré par le tableau)
            'attr' => [
                'class' => 'form-control form-control-sm text-center js-time-input',
                'style' => 'min-width: 90px;'
            ],
        ];

        $builder
            ->add('m_start', TimeType::class, $timeOptions)
            ->add('m_end', TimeType::class, $timeOptions)
            ->add('am_start', TimeType::class, $timeOptions)
            ->add('am_end', TimeType::class, $timeOptions)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
