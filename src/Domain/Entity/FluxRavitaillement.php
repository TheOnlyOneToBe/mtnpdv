<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutFlux;
use App\Domain\ValueObject\Montant;
use App\Infrastructure\Doctrine\Repository\FluxRavitaillementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FluxRavitaillementRepository::class)]
#[ORM\Table(name: 'flux_ravitaillement')]
#[ORM\UniqueConstraint(name: 'UNIQ_FLUX_RAVITAILLEMENT_FACTURE', columns: ['facture_uniq'])]
class FluxRavitaillement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'facture_uniq', type: Types::STRING, length: 50)]
    private string $factureUniq;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'montant_total', type: 'montant', precision: 10, scale: 2)]
    private Montant $montantTotal;

    #[ORM\Column(name: 'statut_flux', type: Types::STRING, length: 50, enumType: StatutFlux::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutFlux $statutFlux = StatutFlux::EN_ATTENTE;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', onDelete: 'SET NULL')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: PointVente::class)]
    #[ORM\JoinColumn(name: 'point_vente_id', onDelete: 'SET NULL')]
    private ?PointVente $pointVente = null;

    /** @var Collection<int, FluxProduit> */
    #[ORM\OneToMany(targetEntity: FluxProduit::class, mappedBy: 'fluxRavitaillement', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct(string $factureUniq)
    {
        $this->factureUniq = $factureUniq;
        $this->dateCreation = new \DateTimeImmutable();
        $this->montantTotal = Montant::zero();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFactureUniq(): string
    {
        return $this->factureUniq;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getMontantTotal(): Montant
    {
        return $this->montantTotal;
    }

    public function getStatutFlux(): StatutFlux
    {
        return $this->statutFlux;
    }

    /**
     * Change le statut en respectant les transitions autorisées du cycle de vie.
     *
     * @throws \DomainException si la transition n'est pas permise
     */
    public function changerStatut(StatutFlux $nouveauStatut): static
    {
        if (!in_array($nouveauStatut, $this->statutFlux->transitionsPossibles(), true)) {
            throw new \DomainException(sprintf(
                'Transition de statut interdite : %s -> %s.',
                $this->statutFlux->value,
                $nouveauStatut->value,
            ));
        }

        $this->statutFlux = $nouveauStatut;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getPointVente(): ?PointVente
    {
        return $this->pointVente;
    }

    public function setPointVente(?PointVente $pointVente): static
    {
        $this->pointVente = $pointVente;

        return $this;
    }

    /** @return Collection<int, FluxProduit> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    /**
     * Ajoute une ligne de produit au flux et recalcule le montant total.
     */
    public function ajouterLigne(Produit $produit, int $quantite, ?Montant $prixUnitaire = null): FluxProduit
    {
        $ligne = new FluxProduit($this, $produit, $quantite, $prixUnitaire ?? $produit->getPrixUnitaire());
        $this->lignes->add($ligne);
        $this->recalculerMontantTotal();

        return $ligne;
    }

    public function retirerLigne(FluxProduit $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            $this->recalculerMontantTotal();
        }

        return $this;
    }

    public function recalculerMontantTotal(): static
    {
        $total = Montant::zero();

        foreach ($this->lignes as $ligne) {
            $total = $total->ajouter($ligne->getSousTotal());
        }

        $this->montantTotal = $total;

        return $this;
    }
}
