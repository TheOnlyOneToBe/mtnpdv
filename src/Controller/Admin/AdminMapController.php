<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\PointVenteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/map', name: 'app_admin_map')]
class AdminMapController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
    ) {
    }

    public function __invoke(): Response
    {
        // try {
            $allPointVentes = $this->pointVentes->findAll();

            // Préparer les données PDV avec statut de seuil
            $pointVentesData = array_map(function ($pdv) {
                return [
                    'id' => $pdv->getId(),
                    'nom' => $pdv->getNomPdv(),
                    'lat' => $pdv->getCoordonnees()->latitude(),
                    'lng' => $pdv->getCoordonnees()->longitude(),
                    'ville' => $pdv->getVille(),
                    'soldeCash' => $pdv->getSoldeCash()->toDecimal(),
                    'soldeFlotte' => $pdv->getSoldeFlotte()->toDecimal(),
                    'seuilMinCash' => $pdv->getSeuilMinCash()->toDecimal(),
                    'seuilMinFlotte' => $pdv->getSeuilMinFlotte()->toDecimal(),
                    'isBelowThreshold' => $pdv->soldeCashEstSousSeuil() || $pdv->soldeFlotteEstSousSeuil(),
                ];
            }, $allPointVentes);
            return $this->render('admin/map.html.twig', [
                'pointVentesData' => $pointVentesData,
            ]);
        // } catch (\Exception $e) {
        //     $this->addFlash('danger', 'Erreur lors du chargement de la carte: '.$e->getMessage());
        //     return $this->render('admin/map.html.twig', [
        //         'pointVentesData' => [],
        //     ]);
        // }
    }
}
