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
        // 1. Création d'un Niveau
        $level = new Level();
        $level->setLevelCode('BTS-SIO-2');
        $level->setLevelName('BTS Services Informatiques aux Organisations 2ème année');
        $manager->persist($level);

        // 2. Création d'un Professeur
        $prof = new Professor();
        $prof->setEmail('prof@lycee.fr');
        $prof->setLastname('Dupont');
        $prof->setFirstname('Jean-Pierre');
        $prof->setRoles(['ROLE_PROFESSOR']);
        $prof->setPassword($this->hasher->hashPassword($prof, 'password'));
        $manager->persist($prof);

        // 3. Création d'un Tuteur
        $tutor = new Tutor();
        $tutor->setEmail('tuteur@entreprise.com');
        $tutor->setLastname('Martin');
        $tutor->setFirstname('Sophie');
        $tutor->setTelMobile('0601020304');
        $tutor->setRoles(['ROLE_TUTOR']);
        $tutor->setPassword($this->hasher->hashPassword($tutor, 'password'));
        $manager->persist($tutor);

        // 4. Création d'une Organisation (COMPLETE)
        $org = new Organisation();
        $org->setName('Tech Solutions');
        $org->setAddressHq('10 rue de la Paix');
        $org->setCityHq('Paris');
        $org->setPostalCodeHq('75000');
        $org->setWebsite('www.techsolutions.com');
        // Champs obligatoires qui manquaient :
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

        // 5. Création d'un Étudiant
        $student = new Student();
        $student->setEmail('eleve@lycee.fr');
        $student->setLastname('Dupont');
        $student->setFirstname('Jean');
        $student->setRoles(['ROLE_STUDENT']);
        $student->setPassword($this->hasher->hashPassword($student, 'password'));
        $student->setPersonalEmail('jean.perso@gmail.com');
        $student->setLevel($level);
        $student->setProfReferent($prof);

        $manager->persist($student);

        // 6. Création d'un Contrat (COMPLET)
        $contract = new Contract();
        $contract->setStatus('En attente');
        $contract->setDeplacement(false);
        $contract->setTransportFreeTaken(true);
        $contract->setLunchTaken(false);
        $contract->setHostTaken(false);
        $contract->setBonus(false);
        $contract->setTokenExpDate(new \DateTime('+1 month'));

        // Remplissage des champs obligatoires pour éviter d'autres erreurs
        $contract->setWorkHours('35h/semaine');
        $contract->setPlannedActivities('Développement Web');
        $contract->setSharingToken('token_test_123'); // Faux token
        $contract->setPdfUnsigned('path/to/unsigned.pdf'); // Faux chemin
        $contract->setPdfSigned('path/to/signed.pdf');     // Faux chemin

        $contract->setStudent($student);
        $contract->setOrganisation($org);
        $contract->setTutor($tutor);
        $contract->setCoordinator($prof);

        $manager->persist($contract);

        $manager->flush();
    }
}
