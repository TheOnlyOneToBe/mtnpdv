<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_active_route', [$this, 'isActiveRoute']),
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
}
