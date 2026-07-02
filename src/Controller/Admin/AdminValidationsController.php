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
class AdminValidationsController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    #[Route('/validations', name: 'validations')]
    public function validations(): Response
    {
        $visitesEnAttente = $this->transactions->findByStatut('EN_ATTENTE');
        $visitesValidees = $this->transactions->findByStatut('VALIDEE');
        $visitesRejetees = $this->transactions->findByStatut('REJETEE');

        return $this->render('admin/validations.html.twig', [
            'visitesEnAttente' => $visitesEnAttente,
            'visitesValidees' => $visitesValidees,
            'visitesRejetees' => $visitesRejetees,
            'nbEnAttente' => count($visitesEnAttente),
            'nbValidees' => count($visitesValidees),
            'nbRejetees' => count($visitesRejetees),
        ]);
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        return $this->render('admin/map.html.twig');
    }
}
