<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'app_admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $visitesEnAttente = $this->transactions->findByStatut('EN_ATTENTE');
        $visitesValidees = $this->transactions->findByStatut('VALIDEE');
        $visitesRejetees = $this->transactions->findByStatut('REJETEE');

        return $this->render('admin/dashboard.html.twig', [
            'visitesEnAttente' => $visitesEnAttente,
            'nbVisitesEnAttente' => count($visitesEnAttente),
            'nbVisitesValidees' => count($visitesValidees),
            'nbVisitesRejetees' => count($visitesRejetees),
        ]);
    }
}
