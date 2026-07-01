<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\Entity\Role;
use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Socle des tests d'intégration : base SQLite recréée avant chaque test
 * à partir du mapping Doctrine (SchemaTool), plus des fabriques de fixtures.
 */
abstract class DoctrineTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    /** @var array<string, Role> rôles déjà créés dans le test courant, indexés par code */
    private array $roles = [];

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->roles = [];

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function creerUtilisateur(string $email = 'jean@exemple.com', string ...$codesRoles): Utilisateur
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString($email),
            password_hash('secret123', \PASSWORD_BCRYPT, ['cost' => 4]),
            Telephone::fromString('+237690123456'),
        );

        foreach ($codesRoles as $code) {
            if (!isset($this->roles[$code])) {
                $this->roles[$code] = new Role($code, ucfirst(strtolower($code)));
                $this->em->persist($this->roles[$code]);
            }

            $utilisateur->addRole($this->roles[$code]);
        }

        $this->em->persist($utilisateur);

        return $utilisateur;
    }

    protected function creerPointVente(string $codeRef = 'PDV-001', ?Utilisateur $gerant = null): PointVente
    {
        $pointVente = new PointVente(
            'Kiosque '.$codeRef,
            $codeRef,
            new Coordonnees(4.0511, 9.7679),
            'Douala',
            Telephone::fromString('+237690777777'),
        );
        $pointVente->setGerant($gerant);

        $this->em->persist($pointVente);

        return $pointVente;
    }

    protected function creerProduit(string $nom = 'Carte SIM', string $prix = '500.00'): Produit
    {
        $produit = new Produit($nom, 'TELECOM', Montant::fromString($prix));

        $this->em->persist($produit);

        return $produit;
    }
}
