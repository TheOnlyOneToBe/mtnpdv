<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
#[Route('/agent', name: 'app_agent_')]
class AgentController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $mesVisites = $this->transactions->findByUtilisateur($user);

        return $this->render('agent/dashboard.html.twig', [
            'mesVisites' => $mesVisites,
            'userLocation' => [
                'latitude' => 4.0511,
                'longitude' => 9.7679,
            ],
        ]);
    }
}
