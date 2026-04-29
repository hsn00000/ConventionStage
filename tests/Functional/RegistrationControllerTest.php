<?php

namespace App\Tests\Functional;

use App\Entity\Level;
use App\Entity\Professor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        static::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase($entityManager);
        $this->seedLevel($entityManager);
        self::ensureKernelShutdown();
    }

    public function testProfessorRegistrationCreatesAccountAndRedirectsToLogin(): void
    {
        $client = static::createClient();
        $levelId = $this->getFirstLevelId();
        $crawler = $client->request('GET', '/register/professor');

        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_professor[email]' => 'prof.test@ac-grenoble.fr',
            'registration_professor[plainPassword]' => 'motdepasse123',
            'registration_professor[lastname]' => 'Martin',
            'registration_professor[firstname]' => 'Claire',
            'registration_professor[sections]' => [(string) $levelId],
            'registration_professor[agreeTerms]' => '1',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/login');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $professor = $entityManager->getRepository(Professor::class)->findOneBy([
            'email' => 'prof.test@ac-grenoble.fr',
        ]);

        self::assertNotNull($professor);
        self::assertContains('ROLE_PROFESSOR', $professor->getRoles());
        self::assertFalse($professor->isVerified());
        self::assertCount(1, $professor->getSections());
    }

    public function testProfessorRegistrationRejectsNonAcademicEmail(): void
    {
        $client = static::createClient();
        $levelId = $this->getFirstLevelId();
        $crawler = $client->request('GET', '/register/professor');

        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_professor[email]' => 'prof.test@gmail.com',
            'registration_professor[plainPassword]' => 'motdepasse123',
            'registration_professor[lastname]' => 'Martin',
            'registration_professor[firstname]' => 'Claire',
            'registration_professor[sections]' => [(string) $levelId],
            'registration_professor[agreeTerms]' => '1',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Vous ne pouvez pas vous enregistrer avec cette adresse email');
    }

    private function resetDatabase(EntityManagerInterface $entityManager): void
    {
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function seedLevel(EntityManagerInterface $entityManager): void
    {
        $level = (new Level())
            ->setLevelCode('BTS1')
            ->setLevelName('BTS SIO 1');

        $entityManager->persist($level);
        $entityManager->flush();
    }

    private function getFirstLevelId(): int
    {
        static::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $level = $entityManager->getRepository(Level::class)->findOneBy(['levelCode' => 'BTS1']);
        self::ensureKernelShutdown();

        return $level->getId();
    }
}
