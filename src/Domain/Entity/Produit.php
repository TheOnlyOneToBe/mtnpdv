<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutProduit;
use App\Infrastructure\Doctrine\Repository\ProduitRepository;
use App\Domain\ValueObject\Montant;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUIT_CODE_BARRE', columns: ['code_barre'])]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_prod', type: Types::STRING, length: 255)]
    private string $nomProd;

    #[ORM\Column(name: 'type_pro', type: Types::STRING, length: 50)]
    private string $typePro;

    #[ORM\Column(name: 'prix_unitaire', type: 'montant', precision: 10, scale: 2)]
    private Montant $prixUnitaire;

    #[ORM\Column(name: 'statut_prod', type: Types::SMALLINT, enumType: StatutProduit::class, options: ['default' => 1])]
    private StatutProduit $statutProd = StatutProduit::ACTIF;

    #[ORM\Column(name: 'code_barre', type: Types::STRING, length: 100, nullable: true)]
    private ?string $codeBarre = null;

    #[ORM\ManyToOne(targetEntity: CategorieProd::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'categorie_id', onDelete: 'SET NULL')]
    private ?CategorieProd $categorie = null;

    public function __construct(string $nomProd, string $typePro, Montant $prixUnitaire)
    {
        $this->nomProd = $nomProd;
        $this->typePro = $typePro;
        $this->prixUnitaire = $prixUnitaire;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProd(): string
    {
        return $this->nomProd;
    }

    public function setNomProd(string $nomProd): static
    {
        $this->nomProd = $nomProd;

        return $this;
    }

    public function getTypePro(): string
    {
        return $this->typePro;
    }

    public function setTypePro(string $typePro): static
    {
        $this->typePro = $typePro;

        return $this;
    }

    public function getPrixUnitaire(): Montant
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(Montant $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getStatutProd(): StatutProduit
    {
        return $this->statutProd;
    }

    public function setStatutProd(StatutProduit $statutProd): static
    {
        $this->statutProd = $statutProd;

        return $this;
    }

    public function estActif(): bool
    {
        return $this->statutProd->estActif();
    }

    public function getCodeBarre(): ?string
    {
        return $this->codeBarre;
    }

    public function setCodeBarre(?string $codeBarre): static
    {
        $this->codeBarre = $codeBarre;

        return $this;
    }

    public function getCategorie(): ?CategorieProd
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieProd $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }
}
