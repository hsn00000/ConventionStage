<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Professor;
use App\Entity\Student;
use App\Entity\Tutor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status')
            ->add('deplacement')
            ->add('transportFreeTaken')
            ->add('lunchTaken')
            ->add('hostTaken')
            ->add('bonus')
            ->add('workHours')
            ->add('plannedActivities')
            ->add('sharingToken')
            ->add('tokenExpDate')
            ->add('pdfUnsigned')
            ->add('pdfSigned')
            ->add('student', EntityType::class, [
                'class' => Student::class,
                'choice_label' => 'id',
            ])
            ->add('organisation', EntityType::class, [
                'class' => Organisation::class,
                'choice_label' => 'id',
            ])
            ->add('tutor', EntityType::class, [
                'class' => Tutor::class,
                'choice_label' => 'id',
            ])
            ->add('coordinator', EntityType::class, [
                'class' => Professor::class,
                'choice_label' => 'id',
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
