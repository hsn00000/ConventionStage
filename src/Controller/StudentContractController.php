<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Student;
use App\Entity\Tutor;
use App\Entity\User;
use App\Form\InitiateContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
// On n'utilise plus Uuid ici, on fait du natif

#[Route('/etudiant/convention')]
#[IsGranted('ROLE_STUDENT')]
class StudentContractController extends AbstractController
{
    #[Route('/nouveau', name: 'app_student_contract_init')]
    public function init(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(InitiateContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            /** @var Student $student */
            $student = $this->getUser();

            // --- 1. Gestion du Tuteur ---
            $tutorEmail = $data['tutorEmail'];

            // On vérifie d'abord si un utilisateur existe déjà avec cet email (dans toute la table User)
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);

            $tutor = null;

            if ($existingUser) {
                // Si l'utilisateur existe déjà
                if ($existingUser instanceof Tutor) {
                    // C'est bien un tuteur, parfait !
                    $tutor = $existingUser;
                } else {
                    // L'email existe mais ce n'est pas un tuteur (ex: c'est un autre étudiant ou un prof)
                    // On ne peut pas créer un compte tuteur avec cet email. On bloque.
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée par un compte qui n\'est pas un tuteur. Veuillez vérifier l\'adresse.');
                    return $this->redirectToRoute('app_student_contract_init');
                }
            } else {
                // L'utilisateur n'existe pas du tout : on CRÉE le compte Tuteur
                $tutor = new Tutor();
                $tutor->setEmail($tutorEmail);
                // On met des valeurs par défaut pour les champs obligatoires (NOT NULL)
                $tutor->setLastname('A compléter');
                $tutor->setFirstname('A compléter');
                $tutor->setRoles(['ROLE_TUTOR']);

                // On génère un mot de passe aléatoire sécurisé (le tuteur devra le réinitialiser ou se connectera via lien magique)
                $randomPassword = bin2hex(random_bytes(10));
                $tutor->setPassword($passwordHasher->hashPassword($tutor, $randomPassword));

                // On persiste le nouveau tuteur
                $em->persist($tutor);
            }

            // --- 2. Création de l'Organisation ---
            // On crée une organisation temporaire liée à ce contrat
            $org = new Organisation();
            $org->setName($data['companyName']);

            // Remplissage des champs obligatoires par des valeurs d'attente
            $org->setAddressHq('A compléter');
            $org->setPostalCodeHq('00000');
            $org->setCityHq('A compléter');
            $org->setAddressInternship('A compléter');
            $org->setPostalCodeInternship('00000');
            $org->setCityInternship('A compléter');
            $org->setRespName('A compléter');
            $org->setRespFunction('A compléter');
            $org->setRespEmail($tutorEmail); // On utilise l'email du tuteur comme contact par défaut
            $org->setRespPhone('0000000000');
            $org->setInsuranceName('A compléter');
            $org->setInsuranceContract('A compléter');

            $em->persist($org);

            // --- 3. Création du Contrat ---
            $contract = new Contract();
            $contract->setStudent($student);
            $contract->setTutor($tutor); // On lie le tuteur (nouveau ou existant)
            $contract->setOrganisation($org);
            $contract->setStatus('En attente entreprise');

            // Champs obligatoires du contrat (valeurs par défaut)
            $contract->setDeplacement(false);
            $contract->setTransportFreeTaken(false);
            $contract->setLunchTaken(false);
            $contract->setHostTaken(false);
            $contract->setBonus(false);
            $contract->setPlannedActivities('A compléter par l\'entreprise');
            $contract->setWorkHours([]); // Tableau vide pour le JSON
            $contract->setPdfUnsigned('');
            $contract->setPdfSigned('');

            // Génération du token pour le lien de partage
            $token = bin2hex(random_bytes(32));
            $contract->setSharingToken($token);

            // Liaison du prof référent (Obligatoire en base)
            if ($student->getProfReferent()) {
                $contract->setCoordinator($student->getProfReferent());
            } else {
                // Si l'étudiant n'a pas de prof, cela va planter à l'enregistrement (coordinator_id NOT NULL)
                // Pour éviter le crash en démo, on cherche le premier prof dispo (Solution de secours)
                // Idéalement : l'étudiant DOIT avoir un prof assigné.
                // $firstProf = $em->getRepository(Professor::class)->findOneBy([]);
                // if ($firstProf) $contract->setCoordinator($firstProf);
            }

            $em->persist($contract);
            $em->flush();

            // --- 4. Envoi de l'Email ---
            $email = (new TemplatedEmail())
                ->from('convention@lycee-faure.fr')
                ->to($tutorEmail)
                ->subject('Lycée Gabriel Fauré - Demande de Convention de Stage')
                ->htmlTemplate('emails/company_request.html.twig')
                ->context([
                    'student' => $student,
                    'contract' => $contract,
                    'token' => $token,
                ]);

            $mailer->send($email);

            // --- MESSAGE DE VALIDATION ---
            $this->addFlash('success', 'La demande a bien été envoyée ! Un email a été transmis au responsable de l\'entreprise.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('student_contract/init.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
