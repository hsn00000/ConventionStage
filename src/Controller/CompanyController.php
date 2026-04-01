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
    private Registry $workflowRegistry;

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

        $workflow = $this->workflowRegistry->get($contract);

        if (!$workflow->can($contract, 'fill_by_company')) {
            return $this->render('company/already_filled.html.twig', [
                'contract' => $contract,
            ]);
        }

        $form = $this->createForm(CompanyFillContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $workflow->apply($contract, 'fill_by_company');
            } catch (\LogicException $e) {
                return $this->render('company/already_filled.html.twig', [
                    'contract' => $contract,
                ]);
            }

            $entityManager->persist($contract);
            $entityManager->flush();

            // Envoi de l'email à l'étudiant pour validation
            $student = $contract->getStudent();

            if ($student && $student->getEmail()) {
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Convention Stage'))
                    ->to($student->getEmail())
                    ->subject('Votre convention a été complétée par l’entreprise')
                    ->htmlTemplate('emails/student_validation_request.html.twig')
                    ->context([
                        'contract' => $contract,
                        'student' => $student,
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
