<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
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
        private readonly RateLimiterFactory $searchLimiter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Apply rate limiting (60 requests per minute per user)
        $limiter = $this->searchLimiter->create($this->getUser()->getUserIdentifier());
        if (!$limiter->consume(1)->isAccepted()) {
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
    }
}
