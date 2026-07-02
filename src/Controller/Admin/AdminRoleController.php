<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/roles', name: 'app_admin_role_')]
class AdminRoleController extends AbstractController
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $roles = $this->roles->findAll();

        return $this->render('admin/role/list.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Role $role): Response
    {
        return $this->render('admin/role/show.html.twig', [
            'role' => $role,
        ]);
    }
}
