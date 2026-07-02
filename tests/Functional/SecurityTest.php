<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecurityTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function creerUtilisateur(string $email, string $motDePasse, bool $actif = true): Utilisateur
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString($email),
            'temporaire',
            Telephone::fromString('+237690123456'),
        );

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $utilisateur->setMotPass($hasher->hashPassword($utilisateur, $motDePasse));

        if (!$actif) {
            $utilisateur->desactiver();
        }

        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    public function testPageDeConnexionAccessible(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'MTNPDV');
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testConnexionReussieRedirigeVersLAccueil(): void
    {
        $this->creerUtilisateur('jean@exemple.com', 'motdepasse');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'jean@exemple.com',
            '_password' => 'motdepasse',
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Jean Dupont');
    }

    public function testMauvaisMotDePasseAfficheUneErreur(): void
    {
        $this->creerUtilisateur('jean@exemple.com', 'motdepasse');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'jean@exemple.com',
            '_password' => 'mauvais',
        ]);

        self::assertResponseRedirects('/login');

        $this->client->followRedirect();
        self::assertSelectorExists('.alert');
    }

    public function testCompteDesactiveRefuse(): void
    {
        $this->creerUtilisateur('inactif@exemple.com', 'motdepasse', actif: false);

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'inactif@exemple.com',
            '_password' => 'motdepasse',
        ]);

        self::assertResponseRedirects('/login');

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'désactivé');
    }

    public function testDeconnexion(): void
    {
        $this->creerUtilisateur('jean@exemple.com', 'motdepasse');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'jean@exemple.com',
            '_password' => 'motdepasse',
        ]);
        $this->client->followRedirect();

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects();

        // De retour sur l'accueil, l'utilisateur n'est plus connecté et est redirigé vers login
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Se connecter');
    }
}
