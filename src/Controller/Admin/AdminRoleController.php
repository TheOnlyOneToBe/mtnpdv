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
        return $this->render('admin/role/show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Role $role, Request $request): Response
    {
        try {
            if ($request->isMethod('POST')) {
                $codeRole = $request->request->get('codeRole');
                $libelle = $request->request->get('libelle');

                if (empty($codeRole) || empty($libelle)) {
                    $this->addFlash('danger', 'Le code et le libellé du rôle sont obligatoires.');
                } else {
                    $role->setCodeRole($codeRole);
                    $role->setLibelle($libelle);
                    $this->roles->save($role);
                    $this->addFlash('success', 'Rôle modifié avec succès.');
                    return $this->redirectToRoute('app_admin_role_show', ['id' => $role->getId()]);
                }
            }

            return $this->render('admin/role/edit.html.twig', [
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la modification: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_role_show', ['id' => $role->getId()]);
        }
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Role $role, Request $request): Response
    {
        try {
            if (!$this->isCsrfTokenValid('delete-role-'.$role->getId(), $request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $this->roles->remove($role);
            $this->addFlash('success', 'Rôle supprimé avec succès.');
            return $this->redirectToRoute('app_admin_role_list');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la suppression: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_role_show', ['id' => $role->getId()]);
        }
    }
}
