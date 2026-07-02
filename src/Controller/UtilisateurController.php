<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UtilisateurController extends AbstractController
{
    #[Route('/profil', name: 'app_profil_show')]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('utilisateur/profil/show.html.twig', [
            'utilisateur' => $user,
        ]);
    }
}
