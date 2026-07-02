<?php

declare(strict_types=1);

namespace App\Controller\Gerant;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GERANT')]
#[Route('/gerant', name: 'app_gerant_')]
class GerantController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        return $this->render('gerant/dashboard.html.twig', [
            'gerant' => $user,
        ]);
    }
}
