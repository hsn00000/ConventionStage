<?php

namespace App\Form;

use App\Entity\Session;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('campaign', EntityType::class, [
                'class' => Session::class,
                'choices' => $options['campaign_choices'],
                'choice_label' => static function (Session $campaign): string {
                    $levelName = $campaign->getLevel()?->getLevelName() ?? 'Classe non renseignée';
                    $periods = $campaign->getPeriodsLabel();

                    if ($periods === '') {
                        return sprintf('%s - %s', $campaign->getName(), $levelName);
                    }

                    return sprintf('%s - %s (%s)', $campaign->getName(), $levelName, $periods);
                },
                'label' => 'Campagne de stage',
                'placeholder' => 'Choisissez une campagne',
                'help' => 'La période de stage est définie par la DDF pour votre classe.',
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner une campagne de stage.'])],
            ])
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
        $resolver->setDefaults([
            'campaign_choices' => [],
        ]);
        $resolver->setAllowedTypes('campaign_choices', 'array');
    }
}
