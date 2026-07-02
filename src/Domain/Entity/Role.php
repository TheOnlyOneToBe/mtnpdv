<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Doctrine\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'role')]
#[ORM\UniqueConstraint(name: 'UNIQ_ROLE_CODE', columns: ['code_role'])]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'code_role', type: Types::STRING, length: 100)]
    private string $codeRole;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $libelle;

    /**
     * Utilisateurs ayant ce rôle (inverse de Utilisateur::$roles).
     *
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, mappedBy: 'roles')]
    private Collection $utilisateurs;

    public function __construct(string $codeRole, string $libelle)
    {
        $this->codeRole = strtoupper($codeRole);
        $this->libelle = $libelle;
        $this->utilisateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeRole(): string
    {
        return $this->codeRole;
    }

    public function setCodeRole(string $codeRole): static
    {
        $this->codeRole = strtoupper($codeRole);

        return $this;
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

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): static
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            $utilisateur->addRole($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): static
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            $utilisateur->removeRole($this);
        }

        return $this;
    }
}
