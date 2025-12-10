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
        // 1. On cherche le contrat avec ce token
        $contract = $em->getRepository(Contract::class)->findOneBy(['sharingToken' => $token]);

        if (!$contract) {
            throw $this->createNotFoundException('Ce lien est invalide.');
        }

        // 2. On affiche le formulaire
        $form = $this->createForm(CompanyFillContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract->setStatus('Validé par entreprise');
            $em->flush();
            return $this->render('company/thanks.html.twig'); // Affichage direct de la page merci
        }

        return $this->render('company/fill.html.twig', [
            'form' => $form->createView(),
            'contract' => $contract,
        ]);
    }
}
