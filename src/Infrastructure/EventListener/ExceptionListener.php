<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(private ?SessionInterface $session = null)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Gérer les exceptions d'accès refusé
        if ($exception instanceof AccessDeniedHttpException) {
            if ($this->session) {
                $this->session->getFlashBag()->add('danger', 'Accès refusé. Vous n\'avez pas les permissions pour effectuer cette action.');

                // Rediriger vers la page précédente ou l'accueil
                $request = $event->getRequest();
                $referer = $request->headers->get('referer');

                // Ne pas rediriger si on est déjà en train de traiter une redirection
                if ($referer && !str_contains($referer, '/error')) {
                    $response = new RedirectResponse($referer);
                    $event->setResponse($response);
                }
            }
        }
    }
}
