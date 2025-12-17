<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
// IMPORTANT : L'import ci-dessous est obligatoire
use App\Form\DailyScheduleType;

class WeeklyScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

        foreach ($days as $day) {
            // C'EST ICI LE POINT CRITIQUE :
            $builder->add($day, DailyScheduleType::class, [
                'label' => ucfirst($day),
                  'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
        ]);
    }
}
