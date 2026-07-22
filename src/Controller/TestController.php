<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Supervision\SupervisionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\attribute\IsGranted;

class TestController extends AbstractController
{
    #[Route('/test-toast', name: 'app_test_toast')]
    public function testToast(): Response
    {
        // Ajouter différents types de messages flash
        $this->addFlash('success', '✅ Succès! Votre action a été effectuée avec succès.');
        $this->addFlash('error', '❌ Erreur! Une erreur est survenue lors du traitement.');
        $this->addFlash('warning', '⚠️ Attention! Veuillez vérifier les informations saisies.');
        $this->addFlash('info', 'ℹ️ Information! Ceci est un message d\'information.');

        return $this->render('test/toast.html.twig');
    }

    #[Route('/test-access-denied', name: 'app_test_access_denied')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function testAccessDenied(): Response
    {
        return new Response('Vous ne devriez jamais voir ceci!');
    }

    #[Route('/test-supervision-map-data', name: 'app_test_supervision_map_data')]
    public function testSupervisionMapData(SupervisionService $supervisionService): JsonResponse
    {
        return new JsonResponse($supervisionService->getPdvMapData());
    }
}
