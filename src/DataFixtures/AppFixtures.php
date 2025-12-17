<?php

namespace App\DataFixtures;

use App\Entity\Contract;
use App\Entity\Level;
use App\Entity\Organisation;
use App\Entity\Professor;
use App\Entity\Student;
use App\Entity\Tutor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- 1. CRÉATION DE L'ADMINISTRATEUR ---
        // On utilise l'entité User de base (ou Professor si vous voulez qu'il soit aussi prof)
        $admin = new \App\Entity\User();
        $admin->setEmail('admin@lycee-faure.fr');
        $admin->setLastname('TRUPIN');
        $admin->setFirstname('Sabine');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123')); // Mot de passe à changer
        $admin->setIsVerified(true);

        $manager->persist($admin);
        $manager->flush();

        // --- 2. NIVEAUX (Formations) ---
        $level = new Level();
        $level->setLevelCode('BTS SIO 1');
        $level->setLevelName('Services Informatiques aux Organisations 1ère année');
        $manager->persist($level);

        $level2 = new Level();
        $level2->setLevelCode('BTS SIO 2');
        $level2->setLevelName('Services Informatiques aux Organisations 2ème année');
        $manager->persist($level2);

        // --- 3. PROFESSEUR ---
        $prof = new Professor();
        $prof->setEmail('prof@lycee.fr');
        $prof->setLastname('Dupont');
        $prof->setFirstname('Jean-Pierre');
        $prof->setRoles(['ROLE_PROFESSOR']);
        $prof->setPassword($this->hasher->hashPassword($prof, 'password'));
        $manager->persist($prof);

        // --- 4. TUTEUR ---
        $tutor = new Tutor();
        $tutor->setEmail('tuteur@entreprise.com');
        $tutor->setLastname('Martin');
        $tutor->setFirstname('Sophie');
        $tutor->setTelMobile('0601020304');
        $tutor->setRoles(['ROLE_TUTOR']);
        $tutor->setPassword($this->hasher->hashPassword($tutor, 'password'));
        $manager->persist($tutor);

        // --- 5. ORGANISATION ---
        $org = new Organisation();
        $org->setName('Tech Solutions');
        $org->setAddressHq('10 rue de la Paix');
        $org->setCityHq('Paris');
        $org->setPostalCodeHq('75000');
        $org->setWebsite('www.techsolutions.com');
        $org->setAddressInternship('12 avenue des Champs');
        $org->setCityInternship('Paris');
        $org->setPostalCodeInternship('75000');
        $org->setRespName('Jean Directeur');
        $org->setRespFunction('PDG');
        $org->setRespEmail('direction@techsolutions.com');
        $org->setRespPhone('0102030405');
        $org->setInsuranceName('AXA');
        $org->setInsuranceContract('123456789');
        $manager->persist($org);

        // --- 6. ÉTUDIANT ---
        $student = new Student();
        $student->setEmail('eleve@lycee.fr');
        $student->setLastname('Durand');
        $student->setFirstname('Paul');
        $student->setRoles(['ROLE_STUDENT']);
        $student->setPassword($this->hasher->hashPassword($student, 'password'));
        $student->setPersonalEmail('paul.perso@gmail.com');
        $student->setLevel($level);
        $student->setProfReferent($prof);
        $manager->persist($student);

        // --- 7. CONTRAT ---
        $contract = new Contract();
        $contract->setStatus('En attente');
        $contract->setDeplacement(false);
        $contract->setTransportFreeTaken(true);
        $contract->setLunchTaken(false);
        $contract->setHostTaken(false);
        $contract->setBonus(false);
        $contract->setTokenExpDate(new \DateTime('+1 month'));
        $contract->setWorkHours([
            'lundi'    => ['m_start' => '09:00', 'm_end' => '12:00', 'am_start' => '14:00', 'am_end' => '17:00'],
            'mardi'    => ['m_start' => '09:00', 'm_end' => '12:00', 'am_start' => '14:00', 'am_end' => '17:00'],
            'mercredi' => ['m_start' => '09:00', 'm_end' => '12:00', 'am_start' => '14:00', 'am_end' => '17:00'],
            'jeudi'    => ['m_start' => '09:00', 'm_end' => '12:00', 'am_start' => '14:00', 'am_end' => '17:00'],
            'vendredi' => ['m_start' => '09:00', 'm_end' => '12:00', 'am_start' => '14:00', 'am_end' => '16:00'],
            'samedi'   => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
        ]);
        $contract->setPlannedActivities('Développement Web Symfony');
        $contract->setSharingToken('token_demo_123');
        $contract->setPdfUnsigned('');
        $contract->setPdfSigned('');

        $contract->setStudent($student);
        $contract->setOrganisation($org);
        $contract->setTutor($tutor);
        $contract->setCoordinator($prof);
        $manager->persist($contract);

        $manager->flush();
    }
}
