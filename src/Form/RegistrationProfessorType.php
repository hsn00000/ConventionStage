<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Professor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationProfessorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Email avec indication claire du domaine académique
            ->add('email', EmailType::class, [
                'label' => 'Email Académique (@ac-grenoble.fr)',
                'attr' => ['placeholder' => 'prenom.nom@ac-grenoble.fr',
                            'pattern' => '.*@ac-grenoble\.fr$',]
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom de famille'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom'
            ])

            // --- AJOUT 1 : SÉLECTION DES SECTIONS ---
            ->add('sections', EntityType::class, [
                'class' => Level::class,
                'choice_label' => 'levelName',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Quelles classes gérez-vous ?', // Libellé plus accueillant
                'label_attr' => ['class' => 'fw-bold mb-2'], // Style du titre
                // C'est ici qu'on prépare le terrain pour le CSS
                'row_attr' => ['class' => 'mb-3'],
            ])

            // 3. Mot de passe (Non mappé, hashé dans le contrôleur)
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])

            // 4. Consentement RGPD
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Professor::class, // Important : Lié à Professor pour activer la validation @ac-grenoble.fr
        ]);
    }
}
