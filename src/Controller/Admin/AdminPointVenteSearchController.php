<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Infrastructure\RateLimit\SearchRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/pdv/search', name: 'app_admin_pdv_search')]
class AdminPointVenteSearchController extends AbstractController
{
    private const RESULTS_PER_PAGE = 12;

    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly SearchRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            // Apply rate limiting (60 requests per minute per user)
            if ($this->rateLimiter->isLimited($this->getUser()->getUserIdentifier())) {
                return $this->render('admin/pdv/turbo/search-results.stream.twig', [
                    'pointVentes' => [],
                    'searchTerm' => '',
                    'hasResults' => false,
                    'rateLimited' => true,
                    'department' => '',
                    'gerant' => '',
                    'currentPage' => 1,
                    'totalPages' => 0,
                ]);
            }

            $searchTerm = $request->query->get('q', '');
            $department = $request->query->get('department', '');
            $gerantId = $request->query->get('gerant', '');
            $page = max(1, (int) $request->query->get('page', 1));

            $results = [];

            try {
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
            } catch (\Exception $e) {
                // Log search error but return empty results gracefully
                return $this->render('admin/pdv/turbo/search-results.stream.twig', [
                    'pointVentes' => [],
                    'searchTerm' => $searchTerm,
                    'hasResults' => false,
                    'department' => $department,
                    'gerant' => $gerantId,
                    'currentPage' => 1,
                    'totalPages' => 0,
                    'rateLimited' => false,
                    'error' => 'Erreur lors de la recherche: '.$e->getMessage(),
                ]);
            }

            // Pagination
            $totalResults = count($results);
            $totalPages = (int) ceil($totalResults / self::RESULTS_PER_PAGE);
            $currentPage = min($page, max(1, $totalPages));

            $offset = ($currentPage - 1) * self::RESULTS_PER_PAGE;
            $paginatedResults = array_slice($results, $offset, self::RESULTS_PER_PAGE);

            return $this->render('admin/pdv/turbo/search-results.stream.twig', [
                'pointVentes' => $paginatedResults,
                'searchTerm' => $searchTerm,
                'hasResults' => !empty($results),
                'department' => $department,
                'gerant' => $gerantId,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalResults' => $totalResults,
                'resultsPerPage' => self::RESULTS_PER_PAGE,
                'rateLimited' => false,
            ]);
        } catch (\Exception $e) {
            return $this->render('admin/pdv/turbo/search-results.stream.twig', [
                'pointVentes' => [],
                'searchTerm' => '',
                'hasResults' => false,
                'department' => '',
                'gerant' => '',
                'currentPage' => 1,
                'totalPages' => 0,
                'rateLimited' => false,
                'error' => 'Erreur critique de recherche: '.$e->getMessage(),
            ]);
        }
    }
}
