<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/roles', name: 'app_admin_role_')]
class AdminRoleController extends AbstractController
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $allRoles = $this->roles->findAll();

            $pagination = $this->paginationService->paginate($allRoles, $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);

            return $this->render('admin/role/list.html.twig', [
                'roles' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de la liste: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Role $role): Response
    {
        // try {
            return $this->render('admin/role/show.html.twig', [
                'role' => $role,
            ]);
        // } catch (\Exception $e) {
        //     $this->addFlash('danger', 'Erreur lors du chargement du rôle: '.$e->getMessage());
        //     return $this->redirectToRoute('app_admin_role_list');
        // }
    }
}
