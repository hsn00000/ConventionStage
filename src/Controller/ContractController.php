<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Student;
use App\Form\ContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/convention')]
class ContractController extends AbstractController
{
    #[Route('/nouvelle', name: 'app_contract_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT')] // Seul un étudiant peut accéder à cette page
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contract = new Contract();

        // 1. Récupération de l'étudiant connecté
        /** @var Student $user */
        $user = $this->getUser();

        // 2. Pré-remplissage des données obligatoires non saisies
        $contract->setStudent($user);
        $contract->setStatus(Contract::STATUS_COLLECTION_SENT);

        // On assigne automatiquement le prof référent si l'étudiant en a un
        if ($user->getProfReferent()) {
            $contract->setCoordinator($user->getProfReferent());
        }

        // 3. Création et traitement du formulaire
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // TODO: Plus tard, on pourra générer ici le "sharingToken" ou les PDF

            $entityManager->persist($contract);
            $entityManager->flush();

            $this->addFlash('success', 'Votre convention a été créée avec succès !');

            // Redirection vers la page d'accueil ou une liste (à créer plus tard)
            return $this->redirectToRoute('app_home');
        }

        return $this->render('contract/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Étape 2 : L'étudiant relit les informations saisies par l'entreprise
     */
    #[Route('/{id}', name: 'app_contract_show', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function show(Contract $contract): Response
    {
        if ($this->getUser() !== $contract->getStudent()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à consulter cette convention.');
        }

        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    /**
     * Étape 2 : L'étudiant valide les informations saisies par l'entreprise
     */
    #[Route('/{id}/valider', name: 'app_contract_validate_by_student', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function validateByStudent(
        Request $request,
        Contract $contract,
        WorkflowInterface $contractWorkflowStateMachine,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {

        // Sécurité : Vérifier que seul l'étudiant propriétaire peut valider
        if ($this->getUser() !== $contract->getStudent()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à valider cette convention.');
        }

        // Sécurité CSRF : Vérification du token envoyé par le formulaire
        if ($this->isCsrfTokenValid('validate_by_student'.$contract->getId(), $request->request->get('_token'))) {

            // Workflow : Vérifier si la transition 'validate_by_student' est autorisée depuis l'état actuel
            if ($contractWorkflowStateMachine->can($contract, 'validate_by_student')) {

                // Application de la transition (le statut passe à 'validated_by_student')
                $contractWorkflowStateMachine->apply($contract, 'validate_by_student');
                $entityManager->flush();

                $coordinator = $contract->getCoordinator();

                if ($coordinator?->getEmail()) {
                    $mailer->send(
                        (new TemplatedEmail())
                            ->from(new Address('no-reply@lycee-faure.fr', 'Conventio'))
                            ->to($coordinator->getEmail())
                            ->subject('Convention a valider pedagogiquement')
                            ->htmlTemplate('emails/professor_validation_request.html.twig')
                            ->context([
                                'contract' => $contract,
                                'professor' => $coordinator,
                            ])
                    );
                }

                $this->addFlash('success', 'La convention a été validée avec succès et transmise à votre professeur.');
            } else {
                $this->addFlash('error', 'Impossible de valider cette convention dans son état actuel.');
            }
        } else {
            $this->addFlash('error', 'Action non autorisée (Jeton CSRF invalide).');
        }

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }
}
