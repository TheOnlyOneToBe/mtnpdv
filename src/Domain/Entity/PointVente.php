<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutPointVente;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use App\Infrastructure\Doctrine\Repository\PointVenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointVenteRepository::class)]
#[ORM\Table(name: 'point_vente')]
#[ORM\UniqueConstraint(name: 'UNIQ_POINT_VENTE_CODE_REF', columns: ['code_ref'])]
class PointVente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_pdv', type: Types::STRING, length: 255)]
    private string $nomPdv;

    #[ORM\Column(name: 'code_ref', type: Types::STRING, length: 100)]
    private string $codeRef;

    #[ORM\Embedded(class: Coordonnees::class, columnPrefix: false)]
    private Coordonnees $coordonnees;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $ville;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'statut_actuel', type: Types::STRING, length: 255, enumType: StatutPointVente::class)]
    private StatutPointVente $statutActuel = StatutPointVente::ACTIF;

    #[ORM\Column(type: 'telephone', length: 20)]
    private Telephone $telephone;

    #[ORM\ManyToOne(targetEntity: CategoriePdv::class, inversedBy: 'pointsVente')]
    #[ORM\JoinColumn(name: 'categorie_pdv_id', onDelete: 'SET NULL')]
    private ?CategoriePdv $categoriePdv = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'gerant_id', onDelete: 'SET NULL')]
    private ?Utilisateur $gerant = null;

    #[ORM\Column(name: 'solde_cash', type: 'montant', precision: 10, scale: 2, options: ['default' => 0])]
    private Montant $soldeCash;

    #[ORM\Column(name: 'solde_flotte', type: 'montant', precision: 10, scale: 2, options: ['default' => 0])]
    private Montant $soldeFlotte;

    #[ORM\Column(name: 'seuil_min_cash', type: 'montant', precision: 10, scale: 2, options: ['default' => 0])]
    private Montant $seuilMinCash;

    #[ORM\Column(name: 'seuil_min_flotte', type: 'montant', precision: 10, scale: 2, options: ['default' => 0])]
    private Montant $seuilMinFlotte;

    /** @var Collection<int, AttributionPdv> */
    #[ORM\OneToMany(targetEntity: AttributionPdv::class, mappedBy: 'pointVente', cascade: ['remove'])]
    private Collection $attributions;

    public function __construct(
        string $nomPdv,
        string $codeRef,
        Coordonnees $coordonnees,
        string $ville,
        Telephone $telephone,
        Montant $seuilMinCash = null,
        Montant $seuilMinFlotte = null,
    ) {
        $this->nomPdv = $nomPdv;
        $this->codeRef = $codeRef;
        $this->coordonnees = $coordonnees;
        $this->ville = $ville;
        $this->telephone = $telephone;
        $this->dateCreation = new \DateTimeImmutable();
        $this->soldeCash = Montant::zero();
        $this->soldeFlotte = Montant::zero();
        $this->seuilMinCash = $seuilMinCash ?? Montant::zero();
        $this->seuilMinFlotte = $seuilMinFlotte ?? Montant::zero();
        $this->attributions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomPdv(): string
    {
        return $this->nomPdv;
    }

    public function setNomPdv(string $nomPdv): static
    {
        $this->nomPdv = $nomPdv;

        return $this;
    }

    public function getCodeRef(): string
    {
        return $this->codeRef;
    }

    public function setCodeRef(string $codeRef): static
    {
        $this->codeRef = $codeRef;

        return $this;
    }

    public function getCoordonnees(): Coordonnees
    {
        return $this->coordonnees;
    }

    public function setCoordonnees(Coordonnees $coordonnees): static
    {
        $this->coordonnees = $coordonnees;

        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getStatutActuel(): StatutPointVente
    {
        return $this->statutActuel;
    }

    public function setStatutActuel(StatutPointVente $statutActuel): static
    {
        $this->statutActuel = $statutActuel;

        return $this;
    }

    public function estOperationnel(): bool
    {
        return $this->statutActuel->estOperationnel();
    }

    public function getTelephone(): Telephone
    {
        return $this->telephone;
    }

    public function setTelephone(Telephone $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getCategoriePdv(): ?CategoriePdv
    {
        return $this->categoriePdv;
    }

    public function setCategoriePdv(?CategoriePdv $categoriePdv): static
    {
        $this->categoriePdv = $categoriePdv;

        return $this;
    }

    public function getGerant(): ?Utilisateur
    {
        return $this->gerant;
    }

    public function setGerant(?Utilisateur $gerant): static
    {
        $this->gerant = $gerant;

        return $this;
    }

    public function getSoldeCash(): Montant
    {
        return $this->soldeCash;
    }

    public function getSoldeFlotte(): Montant
    {
        return $this->soldeFlotte;
    }

    public function getSeuilMinCash(): Montant
    {
        return $this->seuilMinCash;
    }

    public function setSeuilMinCash(Montant $seuilMinCash): static
    {
        $this->seuilMinCash = $seuilMinCash;

        return $this;
    }

    public function getSeuilMinFlotte(): Montant
    {
        return $this->seuilMinFlotte;
    }

    public function setSeuilMinFlotte(Montant $seuilMinFlotte): static
    {
        $this->seuilMinFlotte = $seuilMinFlotte;

        return $this;
    }

    public function ajouterCash(Montant $montant): static
    {
        $this->soldeCash = $this->soldeCash->add($montant);

        return $this;
    }

    public function ajouterFlotte(Montant $montant): static
    {
        $this->soldeFlotte = $this->soldeFlotte->add($montant);

        return $this;
    }

    public function soldeCashEstSousSeuil(): bool
    {
        return $this->soldeCash->lessThan($this->seuilMinCash);
    }

    public function soldeFlotteEstSousSeuil(): bool
    {
        return $this->soldeFlotte->lessThan($this->seuilMinFlotte);
    }

    /** @return Collection<int, AttributionPdv> */
    public function getAttributions(): Collection
    {
        return $this->attributions;
    }
}
