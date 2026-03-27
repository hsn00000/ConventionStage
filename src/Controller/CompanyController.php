<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Form\CompanyFillContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry;

class CompanyController extends AbstractController
{
    private $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    #[Route('/company/fill/{token}', name: 'app_company_fill')]
    public function fill(
        string $token,
        Request $request,
        ContractRepository $contractRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response
    {
        $contract = $contractRepository->findOneBy(['sharingToken' => $token]);

        if (!$contract) {
            throw $this->createNotFoundException('Contrat non trouvé');
        }

        // Nettoyer les valeurs placeholder avant d'afficher le formulaire
        $placeholders = ['A compléter', 'A compléter par l\'entreprise'];
        $numericPlaceholders = ['00000', '0000000000'];

        $org = $contract->getOrganisation();
        if ($org) {
            foreach (['AddressHq', 'PostalCodeHq', 'CityHq', 'AddressInternship', 'PostalCodeInternship', 'CityInternship', 'RespName', 'RespFunction', 'RespPhone', 'InsuranceName', 'InsuranceContract'] as $field) {
                $getter = 'get' . $field;
                $setter = 'set' . $field;
                $value = $org->$getter();
                if (in_array($value, $placeholders) || in_array($value, $numericPlaceholders)) {
                    $org->$setter('');
                }
            }
        }

        $tutor = $contract->getTutor();
        if ($tutor) {
            foreach (['Lastname', 'Firstname'] as $field) {
                $getter = 'get' . $field;
                $setter = 'set' . $field;
                $value = $tutor->$getter();
                if (in_array($value, $placeholders)) {
                    $tutor->$setter('');
                }
            }
        }

        if (in_array($contract->getPlannedActivities(), $placeholders)) {
            $contract->setPlannedActivities('');
        }

        $form = $this->createForm(CompanyFillContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // On récupère le workflow
            $workflow = $this->workflowRegistry->get($contract);

            // On vérifie si on peut passer à l'étape suivante
            if ($workflow->can($contract, 'fill_by_company')) {
                try {
                    $workflow->apply($contract, 'fill_by_company');
                } catch (\LogicException $e) {
                    // Erreur silencieuse ou log
                }
            }

            $entityManager->persist($contract);
            $entityManager->flush();

            // --- Envoi de l'email au Professeur ---
            $student = $contract->getStudent();
            $professor = $student ? $student->getProfReferent() : null;

            if ($professor && $professor->getEmail()) {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Convention Stage'))
                    ->to($professor->getEmail())
                    ->subject('Validation requise : Stage de ' . $student->getFirstname() . ' ' . $student->getLastname())
                    ->htmlTemplate('emails/professor_validation_request.html.twig')
                    ->context([
                        'contract' => $contract,
                        'professor' => $professor,
                        'student' => $student
                    ]);

                $mailer->send($email);
            }

            return $this->redirectToRoute('app_company_thanks');
        }

        return $this->render('company/fill.html.twig', [
            'form' => $form->createView(),
            'contract' => $contract,
        ]);
    }

    #[Route('/company/thanks', name: 'app_company_thanks')]
    public function thanks(): Response
    {
        return $this->render('company/thanks.html.twig');
    }
}
