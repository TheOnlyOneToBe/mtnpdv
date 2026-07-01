<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Montant;
use App\Infrastructure\Doctrine\Repository\FluxProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FluxProduitRepository::class)]
#[ORM\Table(name: 'flux_produit')]
class FluxProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $quantite;

    #[ORM\Column(name: 'sous_total', type: 'montant', precision: 10, scale: 2)]
    private Montant $sousTotal;

    /**
     * Prix unitaire figé au moment du flux (le prix catalogue peut évoluer ensuite).
     */
    #[ORM\Column(name: 'prix_unitaire_flux', type: 'montant', precision: 10, scale: 2)]
    private Montant $prixUnitaireFlux;

    #[ORM\ManyToOne(targetEntity: FluxRavitaillement::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'flux_ravitaillement_id', onDelete: 'CASCADE')]
    private ?FluxRavitaillement $fluxRavitaillement;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'produit_id', onDelete: 'SET NULL')]
    private ?Produit $produit;

    public function __construct(
        FluxRavitaillement $fluxRavitaillement,
        Produit $produit,
        int $quantite,
        Montant $prixUnitaireFlux,
    ) {
        if ($quantite <= 0) {
            throw new \DomainException(sprintf('La quantité doit être strictement positive, %d donné.', $quantite));
        }

        $this->fluxRavitaillement = $fluxRavitaillement;
        $this->produit = $produit;
        $this->quantite = $quantite;
        $this->prixUnitaireFlux = $prixUnitaireFlux;
        $this->sousTotal = $prixUnitaireFlux->multiplier($quantite);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function changerQuantite(int $quantite): static
    {
        if ($quantite <= 0) {
            throw new \DomainException(sprintf('La quantité doit être strictement positive, %d donné.', $quantite));
        }

        $this->quantite = $quantite;
        $this->sousTotal = $this->prixUnitaireFlux->multiplier($quantite);
        $this->fluxRavitaillement?->recalculerMontantTotal();

        return $this;
    }

    public function getSousTotal(): Montant
    {
        return $this->sousTotal;
    }

    public function getPrixUnitaireFlux(): Montant
    {
        return $this->prixUnitaireFlux;
    }

    public function getFluxRavitaillement(): ?FluxRavitaillement
    {
        return $this->fluxRavitaillement;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }
}
