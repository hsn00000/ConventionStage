<?php

namespace App\Form;

use App\Entity\Level;
use App\Entity\Professor;
use App\Entity\Session;
use App\Entity\Student;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType; // <--- N'oubliez pas cet import !
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StudentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email -> celui du lycée (identifiant de connexion)',
            ])
            // --- AJOUT DU MOT DE PASSE POUR LES TESTS ---
            ->add('password', PasswordType::class, [
                'required' => true,
                'mapped' => false, // Ne pas mapper directement sur l'entité (pour éviter le texte clair)
                'hash_property_path' => 'password', // Magie Symfony 7 : Ça hash et ça met dans 'password' !
                'label' => 'Mot de passe (identifiant de connexion)',
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom et prénom (ex : Dupont Jean)',
            ])
            ->add('personalEmail')

            ->add('level', EntityType::class, [
                'class' => Level::class,
                'placeholder' => 'Choisir un niveau',
            ])
            ->add('prof_referent', EntityType::class, [
                'class' => Professor::class,
                'placeholder' => 'Choisir un professeur référent',
            ])
            ->add('sessions', EntityType::class, [
                'required' => false,
                'class' => Session::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Student::class,
        ]);
    }
}
