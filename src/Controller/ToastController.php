<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/toast', name: 'app_toast_')]
class ToastController extends AbstractController
{
    /**
     * Display a success toast
     */
    #[Route('/success', name: 'success', methods: ['POST'])]
    public function success(Request $request): Response
    {
        $message = $request->request->get('message', 'Opération réussie');
        $title = $request->request->get('title', 'Succès');

        return $this->render('toast/success.stream.twig', [
            'message' => $message,
            'title' => $title,
        ]);
    }

    /**
     * Display a danger/error toast
     */
    #[Route('/danger', name: 'danger', methods: ['POST'])]
    public function danger(Request $request): Response
    {
        $message = $request->request->get('message', 'Une erreur est survenue');
        $title = $request->request->get('title', 'Erreur');

        return $this->render('toast/danger.stream.twig', [
            'message' => $message,
            'title' => $title,
        ]);
    }

    /**
     * Display a warning toast
     */
    #[Route('/warning', name: 'warning', methods: ['POST'])]
    public function warning(Request $request): Response
    {
        $message = $request->request->get('message', 'Attention');
        $title = $request->request->get('title', 'Attention');

        return $this->render('toast/warning.stream.twig', [
            'message' => $message,
            'title' => $title,
        ]);
    }

    /**
     * Display an info toast
     */
    #[Route('/info', name: 'info', methods: ['POST'])]
    public function info(Request $request): Response
    {
        $message = $request->request->get('message', 'Information');
        $title = $request->request->get('title', 'Information');

        return $this->render('toast/info.stream.twig', [
            'message' => $message,
            'title' => $title,
        ]);
    }

    /**
     * Display a confirmation toast
     */
    #[Route('/confirmation', name: 'confirmation', methods: ['POST'])]
    public function confirmation(Request $request): Response
    {
        $message = $request->request->get('message', 'Êtes-vous sûr?');
        $confirmUrl = $request->request->get('confirmUrl', '#');
        $confirmMethod = $request->request->get('confirmMethod', 'POST');

        return $this->render('toast/confirmation.stream.twig', [
            'message' => $message,
            'confirmUrl' => $confirmUrl,
            'confirmMethod' => $confirmMethod,
        ]);
    }
}
