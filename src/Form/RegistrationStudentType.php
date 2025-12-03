<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Professor;
use App\Entity\Student;
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

class RegistrationStudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Informations de base (Héritées de User)
            ->add('email', EmailType::class, [
                'label' => 'Email du Lycée (@lycee-faure.fr)',
                'attr' => ['placeholder' => 'prenom.nom@lycee-faure.fr']
            ])
            ->add('name', TextType::class, [
                'label' => 'Prénom et Nom'
            ])

            // 2. Mot de passe (Non mappé pour être hashé dans le contrôleur)
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

            // 3. Informations spécifiques Étudiant (Entité Student)
            ->add('personalEmail', EmailType::class, [
                'label' => 'Email personnel (secours)',
                'required' => false, // Facultatif
            ])
            ->add('level', EntityType::class, [
                'class' => Level::class,
                'label' => 'Votre Classe',
                'placeholder' => 'Choisir une classe',
                // Symfony utilisera automatiquement votre méthode __toString() de Level
            ])
            ->add('prof_referent', EntityType::class, [
                'class' => Professor::class,
                'label' => 'Professeur Référent',
                'placeholder' => 'Choisir un professeur',
                // Symfony utilisera automatiquement votre méthode __toString() de User/Professor
            ])

            // 4. Consentement (Obligatoire pour le RGPD)
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
            'data_class' => Student::class, // Très important : lie le formulaire à l'entité Student
        ]);
    }
}
