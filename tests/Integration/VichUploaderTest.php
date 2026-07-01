<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Email;
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

        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
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
        self::assertStringEndsWith('.jpg', $reloaded->getPhotoProfilUrl());
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
}
