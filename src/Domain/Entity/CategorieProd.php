<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\CategorieProdRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieProdRepository::class)]
#[ORM\Table(name: 'categorie_prod')]
class CategorieProd
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $libelle;

    #[ORM\Column(name: 'type_cat', type: Types::STRING, length: 50)]
    private string $typeCat;

    /** @var Collection<int, Produit> */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
    private Collection $produits;

    public function __construct(string $libelle, string $typeCat)
    {
        $this->libelle = $libelle;
        $this->typeCat = $typeCat;
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getTypeCat(): string
    {
        return $this->typeCat;
    }

    public function setTypeCat(string $typeCat): static
    {
        $this->typeCat = $typeCat;

        return $this;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }
}
