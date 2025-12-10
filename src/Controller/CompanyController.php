<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Form\CompanyFillContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/entreprise')]
class CompanyController extends AbstractController
{
    #[Route('/collecte/{token}', name: 'app_company_fill')]
    public function fill(string $token, Request $request, EntityManagerInterface $em): Response
    {
        // 1. Recherche du contrat via le token unique
        $contract = $em->getRepository(Contract::class)->findOneBy(['sharingToken' => $token]);

        // Sécurité 1 : Lien invalide ou introuvable
        if (!$contract) {
            throw $this->createNotFoundException('Ce lien de convention est invalide ou a expiré.');
        }

        // Sécurité 2 : Empêcher de modifier si déjà validé
        if ($contract->getStatus() === 'Validé par entreprise' || $contract->getStatus() === 'Validé') {
            return $this->render('company/already_filled.html.twig');
        }

        // 2. Création du formulaire
        $form = $this->createForm(CompanyFillContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 3. Validation
            $contract->setStatus('Validé par entreprise');

            // On enregistre toutes les modifications (Contrat + Organisation + Tuteur)
            $em->flush();

            return $this->redirectToRoute('app_company_thanks');
        }

        return $this->render('company/fill.html.twig', [
            'form' => $form->createView(),
            'contract' => $contract, // Pour afficher le nom de l'étudiant
        ]);
    }

    #[Route('/merci', name: 'app_company_thanks')]
    public function thanks(): Response
    {
        return $this->render('company/thanks.html.twig');
    }
}
