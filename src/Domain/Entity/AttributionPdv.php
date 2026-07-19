<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\AttributionPdvRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttributionPdvRepository::class)]
#[ORM\Table(name: 'attribution_pdv')]
#[ORM\Index(name: 'IDX_ATTRIBUTION_AGENT', columns: ['agent_id'])]
#[ORM\Index(name: 'IDX_ATTRIBUTION_PDV', columns: ['point_vente_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_ATTRIBUTION_AGENT_PDV_ACTIF', columns: ['agent_id', 'point_vente_id'], options: ['where' => 'actif = 1'])]
class AttributionPdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'attributionsPdv')]
    #[ORM\JoinColumn(name: 'agent_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $agent;

    #[ORM\ManyToOne(targetEntity: PointVente::class, inversedBy: 'attributions')]
    #[ORM\JoinColumn(name: 'point_vente_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PointVente $pointVente;

    #[ORM\Column(name: 'date_attribution', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateAttribution;

    #[ORM\Column(name: 'date_retrait', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateRetrait = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    public function __construct(Utilisateur $agent, PointVente $pointVente, ?\DateTimeImmutable $dateAttribution = null)
    {
        $this->agent = $agent;
        $this->pointVente = $pointVente;
        $this->dateAttribution = $dateAttribution ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgent(): Utilisateur
    {
        return $this->agent;
    }

    public function getPointVente(): PointVente
    {
        return $this->pointVente;
    }

    public function getDateAttribution(): \DateTimeImmutable
    {
        return $this->dateAttribution;
    }

    public function getDateRetrait(): ?\DateTimeImmutable
    {
        return $this->dateRetrait;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function revoir(): static
    {
        $this->actif = false;
        $this->dateRetrait = new \DateTimeImmutable();

        return $this;
    }

    public function retablir(): static
    {
        $this->actif = true;
        $this->dateRetrait = null;

        return $this;
    }

    public function setDateRetrait(?\DateTimeImmutable $dateRetrait): static
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }
}
