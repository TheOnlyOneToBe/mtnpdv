<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/pdv/search', name: 'app_admin_pdv_search')]
class AdminPointVenteSearchController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        $department = $request->query->get('department', '');
        $gerantId = $request->query->get('gerant', '');

        $results = [];

        if ($searchTerm) {
            $results = $this->pointVentes->rechercher($searchTerm);
        } elseif ($department) {
            $results = $this->pointVentes->findByVille($department);
        } elseif ($gerantId) {
            $gerant = $this->utilisateurs->find((int) $gerantId);
            if ($gerant) {
                $results = $this->pointVentes->findByGerant($gerant);
            }
        }

        return $this->render('admin/pdv/turbo/search-results.stream.twig', [
            'pointVentes' => $results,
            'searchTerm' => $searchTerm,
            'hasResults' => !empty($results),
        ]);
    }
}
