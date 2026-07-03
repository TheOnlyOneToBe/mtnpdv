<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\ValueObject\Email;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test flash messages and toasts display
 */
class FlashMessagesTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testFlashMessageDisplayedOnProfileEdit(): void
    {
        // Login as admin
        $this->client->loginUser($this->getAdminUser());

        // Access edit profile form
        $crawler = $this->client->request('GET', '/profil/edit');
        $this->assertResponseIsSuccessful();

        // Submit form with valid data
        $form = $crawler->selectButton('Enregistrer les modifications')->form();
        $form['utilisateur_type[prenomUt]'] = 'Jean';
        $form['utilisateur_type[nomUt]'] = 'Dupont';

        $this->client->submit($form);

        // Should redirect to profile show
        $this->assertResponseRedirects('/profil');

        // Follow redirect
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Check that flash message is rendered in HTML
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('data-flash-type', $html, 'Flash message container should be present');
        $this->assertStringContainsString('data-controller="flash-toast"', $html, 'Flash toast controller should be registered');

        // The flash message should contain success message about profile update
        $this->assertTrue(
            strpos($html, 'mis à jour') !== false || strpos($html, 'succès') !== false || strpos($html, 'modifié') !== false,
            'Success message should be displayed'
        );
    }

    public function testFlashMessageOnLogin(): void
    {
        // Access login page
        $crawler = $this->client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        // Submit login form with valid credentials
        $form = $crawler->selectButton('Se connecter')->form();
        $form['email'] = 'admin@mtnpdv.test';
        $form['password'] = 'password123';

        $this->client->submit($form);

        // Should redirect to home
        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();

        // Check HTML for toast controller
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('data-controller="toast"', $html, 'Toast controller should be registered');
    }

    public function testToastContainerPresent(): void
    {
        // Login
        $this->client->loginUser($this->getAdminUser());

        // Access any page
        $crawler = $this->client->request('GET', '/profil');
        $this->assertResponseIsSuccessful();

        $html = $this->client->getResponse()->getContent();

        // Check for toast container
        $this->assertStringContainsString('id="toast-container"', $html, 'Toast container should be present');
        $this->assertStringContainsString('data-controller="toast"', $html, 'Toast controller should be active');

        // Check for flash-toast controller
        $this->assertStringContainsString('data-controller="flash-toast"', $html, 'Flash toast converter should be active');
    }

    public function testFlashMessageOnPasswordChange(): void
    {
        // Login as admin
        $this->client->loginUser($this->getAdminUser());

        // Access change password page
        $crawler = $this->client->request('GET', '/profil/change-password');
        $this->assertResponseIsSuccessful();

        // Submit password change form
        $form = $crawler->selectButton('Changer le mot de passe')->form([
            'current_password' => 'password123',
            'new_password' => 'newPassword123',
            'confirm_password' => 'newPassword123',
        ]);

        $this->client->submit($form);

        // Should redirect back to profile
        $this->assertResponseRedirects('/profil');
        $crawler = $this->client->followRedirect();

        // Check for flash message
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('data-flash-type', $html);
    }

    private function getAdminUser()
    {
        $userRepository = static::getContainer()->get('doctrine')->getRepository('App\Domain\Entity\Utilisateur');
        return $userRepository->findOneByEmail(Email::fromString('admin@mtnpdv.test'));
    }
}
