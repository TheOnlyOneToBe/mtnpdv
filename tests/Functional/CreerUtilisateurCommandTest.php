<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Role;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CreerUtilisateurCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Rôles normalement insérés par la migration de seed
        $this->em->persist(new Role('ADMIN', 'Administrateur'));
        $this->em->persist(new Role('AGENT', 'Agent commercial'));
        $this->em->flush();

        $application = new Application(self::$kernel);
        $this->commandTester = new CommandTester($application->find('app:utilisateur:creer'));
    }

    public function testCreationDUnAdministrateur(): void
    {
        $code = $this->commandTester->execute([
            'email' => 'admin@exemple.com',
            '--nom' => 'Super',
            '--prenom' => 'Admin',
            '--mot-de-passe' => 'motdepasse-solide',
            '--role' => ['ADMIN'],
        ]);

        self::assertSame(Command::SUCCESS, $code);

        $repo = self::getContainer()->get(UtilisateurRepositoryInterface::class);
        $admin = $repo->findOneByEmail(Email::fromString('admin@exemple.com'));

        self::assertNotNull($admin);
        self::assertContains('ROLE_ADMIN', $admin->getRoles());
        // Le mot de passe est stocké haché, jamais en clair
        self::assertNotSame('motdepasse-solide', $admin->getMotPass());
    }

    public function testEmailDuplique(): void
    {
        $this->commandTester->execute(['email' => 'doublon@exemple.com', '--mot-de-passe' => 'motdepasse1']);
        $code = $this->commandTester->execute(['email' => 'doublon@exemple.com', '--mot-de-passe' => 'motdepasse2']);

        self::assertSame(Command::FAILURE, $code);
        self::assertStringContainsString('existe déjà', $this->commandTester->getDisplay());
    }

    public function testRoleInconnu(): void
    {
        $code = $this->commandTester->execute([
            'email' => 'x@exemple.com',
            '--mot-de-passe' => 'motdepasse1',
            '--role' => ['INEXISTANT'],
        ]);

        self::assertSame(Command::FAILURE, $code);
        self::assertStringContainsString('Rôle inconnu', $this->commandTester->getDisplay());
    }

    public function testEmailInvalide(): void
    {
        $code = $this->commandTester->execute([
            'email' => 'pas-un-email',
            '--mot-de-passe' => 'motdepasse1',
        ]);

        self::assertSame(Command::INVALID, $code);
    }
}
