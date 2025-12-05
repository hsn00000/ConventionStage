<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Student;
use App\Form\ContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        $contract->setStatus('Brouillon'); // Statut par défaut

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
}
