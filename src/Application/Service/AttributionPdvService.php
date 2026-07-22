<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\AttributionPdv;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\AttributionPdvRepositoryInterface;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;

final class AttributionPdvService
{
    public function __construct(
        private readonly AttributionPdvRepositoryInterface $attributions,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly PointVenteRepositoryInterface $pointsDeVente,
        private readonly ProduitRepositoryInterface $produits,
    ) {
    }

    /** @return list<Utilisateur> */
    public function listerAgentsDisponibles(): array
    {
        return $this->utilisateurs->findByRole('AGENT');
    }

    /** @return list<PointVente> */
    public function listerPointsDeVenteDisponibles(): array
    {
        return $this->pointsDeVente->findAll();
    }

    public function attribuer(Utilisateur $agent, PointVente $pointVente, array $produitsIds = []): AttributionPdv
    {
        $attributionsActivesPourPdv = $this->attributions->findAttivesByPointVente($pointVente);
        
        foreach ($attributionsActivesPourPdv as $attribution) {
            if ($attribution->getAgent()->getId() === $agent->getId()) {
                throw new \InvalidArgumentException('Cet agent est déjà attribué à ce point de vente.');
            }
        }
        
        if (count($attributionsActivesPourPdv) >= 2) {
            throw new \InvalidArgumentException('Ce point de vente a déjà 2 agents actifs.');
        }

        $attribution = new AttributionPdv($agent, $pointVente);
        $this->attributions->save($attribution);

        foreach ($produitsIds as $produitId) {
            $produit = $this->produits->find($produitId);
            if (!$produit instanceof Produit) {
                throw new \InvalidArgumentException('Produit introuvable.');
            }
        }

        return $attribution;
    }

    public function revoquer(AttributionPdv $attribution): void
    {
        if (!$attribution->isActif()) {
            throw new \InvalidArgumentException('Cette attribution est déjà révoquée.');
        }

        $attribution->revoir();
        $this->attributions->save($attribution);
    }

    public function retablir(AttributionPdv $attribution): void
    {
        if ($attribution->isActif()) {
            throw new \InvalidArgumentException('Cette attribution est déjà active.');
        }

        $attribution->retablir();
        $this->attributions->save($attribution);
    }

    public function findAgent(int $id): Utilisateur
    {
        $agent = $this->utilisateurs->find($id);

        if (!$agent instanceof Utilisateur) {
            throw new \InvalidArgumentException('Agent introuvable.');
        }

        return $agent;
    }

    public function findPointVente(int $id): PointVente
    {
        $pointVente = $this->pointsDeVente->find($id);

        if (!$pointVente instanceof PointVente) {
            throw new \InvalidArgumentException('Point de vente introuvable.');
        }

        return $pointVente;
    }

    public function findProduit(int $id): Produit
    {
        $produit = $this->produits->find($id);

        if (!$produit instanceof Produit) {
            throw new \InvalidArgumentException('Produit introuvable.');
        }

        return $produit;
    }

    /** @return list<AttributionPdv> */
    public function rechercherAttributions(string $terme, ?bool $actif = null): array
    {
        return $this->attributions->search($terme, $actif);
    }

    /** @return list<Produit> */
    public function listerProduitsActifs(): array
    {
        return $this->produits->findActifs();
    }
}
