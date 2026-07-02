<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\CategoriePdvRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriePdvRepository::class)]
#[ORM\Table(name: 'categorie_pdv')]
class CategoriePdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'libelle_catpdv', type: Types::STRING, length: 100)]
    private string $libelleCatpdv;

    /** @var Collection<int, PointVente> */
    #[ORM\OneToMany(targetEntity: PointVente::class, mappedBy: 'categoriePdv')]
    private Collection $pointsVente;

    public function __construct(string $libelleCatpdv)
    {
        $this->libelleCatpdv = $libelleCatpdv;
        $this->pointsVente = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelleCatpdv(): string
    {
        return $this->libelleCatpdv;
    }

    public function setLibelleCatpdv(string $libelleCatpdv): static
    {
        $this->libelleCatpdv = $libelleCatpdv;

        return $this;
    }

    /**
     * Alias de getLibelleCatpdv(), utilisé par les templates.
     */
    public function getNomCategorie(): string
    {
        return $this->libelleCatpdv;
    }

    /** @return Collection<int, PointVente> */
    public function getPointsVente(): Collection
    {
        return $this->pointsVente;
    }
}
