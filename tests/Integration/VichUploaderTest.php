<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class VichUploaderTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private string $uploadDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->uploadDir = self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/profils';

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0o755, true);
        }
    }

    protected function tearDown(): void
    {
        $this->em->close();

        if (is_dir($this->uploadDir)) {
            $files = glob($this->uploadDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    public function testUploadPhotoUtilisateur(): void
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean@exemple.com'),
            'hash',
            Telephone::fromString('+237690123456'),
        );

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/avatar_test_'.uniqid().'.jpg';
        file_put_contents($tempFile, 'dummy image content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'avatar.jpg',
            'image/jpeg',
            null,
            true,
        );

        $utilisateur->setPhotoFile($uploadedFile);
        $this->em->persist($utilisateur);
        $this->em->flush();

        $this->em->clear();

        $reloaded = $this->em->getRepository(Utilisateur::class)->find($utilisateur->getId());

        self::assertNotNull($reloaded);
        self::assertNotNull($reloaded->getPhotoProfilUrl());
        self::assertTrue(is_file($this->uploadDir.'/'.$reloaded->getPhotoProfilUrl()));
    }

    public function testUtilisateurSanPhoto(): void
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean@exemple.com'),
            'hash',
            Telephone::fromString('+237690123456'),
        );

        $this->em->persist($utilisateur);
        $this->em->flush();

        self::assertNull($utilisateur->getPhotoProfilUrl());
    }

    public function testUploadPhotoVisite(): void
    {
        $visitesUploadDir = self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/preuves';

        if (!is_dir($visitesUploadDir)) {
            mkdir($visitesUploadDir, 0o755, true);
        }

        $utilisateur = new Utilisateur(
            'Mbarga',
            'Paul',
            Email::fromString('paul@exemple.com'),
            'hash',
            Telephone::fromString('+237690111111'),
        );
        $this->em->persist($utilisateur);

        $pdv = new PointVente(
            'Kiosque Douala',
            'PDV-001',
            new Coordonnees(3.8480, 11.5021),
            'Douala',
            Telephone::fromString('+237690777777'),
        );
        $this->em->persist($pdv);

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/visite_photo_'.uniqid().'.jpg';
        file_put_contents($tempFile, 'dummy photo content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'visite_proof.jpg',
            'image/jpeg',
            null,
            true,
        );

        $transaction = new Transaction(
            TypeTransaction::VISITE,
            Montant::zero(),
            new Coordonnees(3.8480, 11.5021),
        );
        $transaction->setPointVente($pdv);
        $transaction->setUtilisateur($utilisateur);
        $transaction->setPhotoFile($uploadedFile);

        $this->em->persist($transaction);
        $this->em->flush();

        $this->em->clear();

        $reloaded = $this->em->getRepository(Transaction::class)->find($transaction->getId());

        self::assertNotNull($reloaded);
        self::assertNotNull($reloaded->getPhotoPreuveUrl());
        self::assertTrue(is_file($visitesUploadDir.'/'.$reloaded->getPhotoPreuveUrl()));

        // Cleanup
        if (is_file($visitesUploadDir.'/'.$reloaded->getPhotoPreuveUrl())) {
            @unlink($visitesUploadDir.'/'.$reloaded->getPhotoPreuveUrl());
        }
    }

    public function testVisiteSansPhoto(): void
    {
        $utilisateur = new Utilisateur(
            'Mbarga',
            'Paul',
            Email::fromString('paul@exemple.com'),
            'hash',
            Telephone::fromString('+237690111111'),
        );
        $this->em->persist($utilisateur);

        $pdv = new PointVente(
            'Kiosque Douala',
            'PDV-001',
            new Coordonnees(3.8480, 11.5021),
            'Douala',
            Telephone::fromString('+237690777777'),
        );
        $this->em->persist($pdv);

        $transaction = new Transaction(
            TypeTransaction::VISITE,
            Montant::zero(),
            new Coordonnees(3.8480, 11.5021),
        );
        $transaction->setPointVente($pdv);
        $transaction->setUtilisateur($utilisateur);

        $this->em->persist($transaction);
        $this->em->flush();

        self::assertNull($transaction->getPhotoPreuveUrl());
    }
}
