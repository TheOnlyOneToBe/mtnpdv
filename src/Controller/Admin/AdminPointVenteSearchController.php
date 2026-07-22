<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\CategoriePdv;
use App\Domain\Enum\StatutPointVente;
use App\Domain\Repository\CategoriePdvRepositoryInterface;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Infrastructure\RateLimit\SearchRateLimiter;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly CategoriePdvRepositoryInterface $categoriesPdv,
        private readonly EntityManagerInterface $entityManager,
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
                    'statut' => '',
                    'categorie' => '',
                    'currentPage' => 1,
                    'totalPages' => 0,
                ]);
            }

            $searchTerm = $request->query->get('q', '');
            $department = $request->query->get('department', '');
            $gerantId = $request->query->get('gerant', '');
            $statut = $request->query->get('statut', '');
            $categorieId = $request->query->get('categorie', '');
            $page = max(1, (int) $request->query->get('page', 1));

            $results = [];

            try {
                // Build query with all filters
                $qb = $this->entityManager->createQueryBuilder()
                    ->select('p')
                    ->from('App\Domain\Entity\PointVente', 'p')
                    ->leftJoin('p.gerant', 'g')
                    ->leftJoin('p.categoriePdv', 'c');

                $hasFilters = false;

                if ($searchTerm) {
                    $hasFilters = true;
                    $qb->andWhere('LOWER(p.nomPdv) LIKE LOWER(:searchTerm) OR LOWER(p.ville) LIKE LOWER(:searchTerm) OR LOWER(p.codeRef) LIKE LOWER(:searchTerm)')
                       ->setParameter('searchTerm', '%'.$searchTerm.'%');
                }

                if ($department) {
                    $hasFilters = true;
                    $qb->andWhere('LOWER(p.ville) = LOWER(:department)')
                       ->setParameter('department', $department);
                }

                if ($gerantId) {
                    $hasFilters = true;
                    $gerant = $this->utilisateurs->find((int) $gerantId);
                    if ($gerant) {
                        $qb->andWhere('p.gerant = :gerant')
                           ->setParameter('gerant', $gerant);
                    }
                }

                if ($statut) {
                    $hasFilters = true;
                    $statutEnum = StatutPointVente::tryFrom($statut);
                    if ($statutEnum) {
                        $qb->andWhere('p.statutActuel = :statut')
                           ->setParameter('statut', $statutEnum);
                    }
                }

                if ($categorieId) {
                    $hasFilters = true;
                    $categorie = $this->categoriesPdv->find((int) $categorieId);
                    if ($categorie) {
                        $qb->andWhere('p.categoriePdv = :categorie')
                           ->setParameter('categorie', $categorie);
                    }
                }

                if ($hasFilters) {
                    $results = $qb->getQuery()->getResult();
                } else {
                    $results = $this->pointVentes->findAll();
                }
            } catch (\Exception $e) {
                // Log search error but return empty results gracefully
                return $this->render('admin/pdv/turbo/search-results.stream.twig', [
                    'pointVentes' => [],
                    'searchTerm' => $searchTerm,
                    'hasResults' => false,
                    'department' => $department,
                    'gerant' => $gerantId,
                    'statut' => $statut,
                    'categorie' => $categorieId,
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
                'statut' => $statut,
                'categorie' => $categorieId,
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
                'statut' => '',
                'categorie' => '',
                'currentPage' => 1,
                'totalPages' => 0,
                'rateLimited' => false,
                'error' => 'Erreur critique de recherche: '.$e->getMessage(),
            ]);
        }
    }
}
