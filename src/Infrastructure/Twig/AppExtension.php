<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_active_route', [$this, 'isActiveRoute']),
            new TwigFunction('get_pending_approvisionnements_count', [$this, 'getPendingApprovisionnementsCount']),
        ];
    }

    public function isActiveRoute(string|array $routeName): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        $currentRoute = $request->attributes->get('_route');

        if (is_array($routeName)) {
            return in_array($currentRoute, $routeName, true);
        }

        return $currentRoute === $routeName;
    }

    public function getPendingApprovisionnementsCount(): int
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return 0;
        }

        /** @var Utilisateur|null $user */
        $user = $token->getUser();
        if (!$user instanceof Utilisateur) {
            return 0;
        }

        if (!$user->aLeRole('AGENT')) {
            return 0;
        }

        return count($this->transactionRepository->findPendingApprovisionnementsForAgent($user));
    }
}
