<?php

namespace App\Form;

use App\Entity\Organisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- IDENTITÉ DE L'ENTREPRISE ---
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'organisme / Raison Sociale',
            ])
            ->add('website', TextType::class, [
                'label' => 'Site Web',
                'required' => false,
            ])
            // SIRET (Attention, vérifiez si vous avez ajouté ce champ dans l'entité Organisation)
            // Si le champ n'existe pas encore dans l'entité, commentez cette ligne :
            // ->add('siret', TextType::class, ['label' => 'Numéro SIRET'])

            // --- ADRESSE DU SIÈGE ---
            ->add('addressHq', TextType::class, ['label' => 'Adresse du siège'])
            ->add('postalCodeHq', TextType::class, ['label' => 'Code Postal'])
            ->add('cityHq', TextType::class, ['label' => 'Ville'])

            // --- LIEU DU STAGE (Si différent) ---
            ->add('addressInternship', TextType::class, ['label' => 'Adresse du stage (si différente)', 'required' => false])
            ->add('postalCodeInternship', TextType::class, ['label' => 'Code Postal', 'required' => false])
            ->add('cityInternship', TextType::class, ['label' => 'Ville', 'required' => false])

            // --- RESPONSABLE SIGNATAIRE ---
            ->add('respName', TextType::class, ['label' => 'Nom et Prénom du signataire'])
            ->add('respFunction', TextType::class, ['label' => 'Fonction du signataire'])
            ->add('respEmail', EmailType::class, ['label' => 'Email du signataire'])
            ->add('respPhone', TextType::class, ['label' => 'Téléphone'])

            // --- ASSURANCE ---
            ->add('insuranceName', TextType::class, ['label' => 'Nom de l\'assureur'])
            ->add('insuranceContract', TextType::class, ['label' => 'Numéro de contrat d\'assurance'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,
        ]);
    }
}
