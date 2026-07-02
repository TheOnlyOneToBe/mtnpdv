<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutUtilisateur;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use App\Infrastructure\Doctrine\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\UniqueConstraint(name: 'UNIQ_UTILISATEUR_EMAIL', columns: ['email'])]
#[ORM\Index(name: 'IDX_UTILISATEUR_TELEPHONE', columns: ['telephone'])]
#[Uploadable]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_ut', type: Types::STRING, length: 255)]
    private string $nomUt;

    #[ORM\Column(name: 'prenom_ut', type: Types::STRING, length: 255)]
    private string $prenomUt;

    #[ORM\Column(type: 'email', length: 255)]
    private Email $email;

    #[ORM\Column(name: 'mot_pass', type: Types::STRING, length: 255)]
    private string $motPass;

    #[ORM\Column(type: 'telephone', length: 20)]
    private Telephone $telephone;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: Types::SMALLINT, enumType: StatutUtilisateur::class, options: ['default' => 1])]
    private StatutUtilisateur $statut = StatutUtilisateur::ACTIF;

    #[UploadableField(mapping: 'photos_utilisateur', fileNameProperty: 'photoProfilUrl')]
    private ?File $photoFile = null;

    #[ORM\Column(name: 'photo_profil_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $photoProfilUrl = null;

    #[ORM\Column(name: 'date_photo_update', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $datePhotoUpdate = null;

    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'utilisateur_role')]
    private Collection $roles;

    public function __construct(
        string $nomUt,
        string $prenomUt,
        Email $email,
        string $motPassHache,
        Telephone $telephone,
    ) {
        $this->nomUt = $nomUt;
        $this->prenomUt = $prenomUt;
        $this->email = $email;
        $this->motPass = $motPassHache;
        $this->telephone = $telephone;
        $this->dateCreation = new \DateTimeImmutable();
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomUt(): string
    {
        return $this->nomUt;
    }

    public function setNomUt(string $nomUt): static
    {
        $this->nomUt = $nomUt;

        return $this;
    }

    public function getPrenomUt(): string
    {
        return $this->prenomUt;
    }

    public function setPrenomUt(string $prenomUt): static
    {
        $this->prenomUt = $prenomUt;

        return $this;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenomUt.' '.$this->nomUt);
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotPass(): string
    {
        return $this->motPass;
    }

    /**
     * @param string $motPassHache mot de passe déjà haché (hasher Symfony)
     */
    public function setMotPass(string $motPassHache): static
    {
        $this->motPass = $motPassHache;

        return $this;
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

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getStatut(): StatutUtilisateur
    {
        return $this->statut;
    }

    public function setStatut(StatutUtilisateur $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * Alias de getStatut(), utilisé par les templates et le formulaire UtilisateurType.
     */
    public function getStatutUtilisateur(): StatutUtilisateur
    {
        return $this->statut;
    }

    /**
     * Alias de setStatut(), utilisé par le formulaire UtilisateurType.
     */
    public function setStatutUtilisateur(StatutUtilisateur $statut): static
    {
        return $this->setStatut($statut);
    }

    public function estActif(): bool
    {
        return $this->statut->estActif();
    }

    public function activer(): static
    {
        return $this->setStatut(StatutUtilisateur::ACTIF);
    }

    public function desactiver(): static
    {
        return $this->setStatut(StatutUtilisateur::INACTIF);
    }

    /** @return Collection<int, Role> */
    public function getRolesEntites(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->roles->removeElement($role);

        return $this;
    }

    public function aLeRole(string $codeRole): bool
    {
        $code = strtoupper($codeRole);

        return in_array(str_starts_with($code, 'ROLE_') ? $code : 'ROLE_'.$code, $this->getRoles(), true);
    }

    public function setPhotoFile(?File $photoFile = null): static
    {
        $this->photoFile = $photoFile;

        if (null !== $photoFile) {
            $this->datePhotoUpdate = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoProfilUrl(?string $photoProfilUrl): static
    {
        $this->photoProfilUrl = $photoProfilUrl;

        return $this;
    }

    public function getPhotoProfilUrl(): ?string
    {
        return $this->photoProfilUrl;
    }

    // --- Contrat Symfony Security (UserInterface / PasswordAuthenticatedUserInterface) ---

    /**
     * Identifiant unique utilisé par le firewall (property "email" du provider).
     */
    public function getUserIdentifier(): string
    {
        return $this->email->value();
    }

    /**
     * Codes de rôles au format Symfony (préfixe ROLE_), avec ROLE_USER garanti.
     *
     * @return non-empty-list<string>
     */
    public function getRoles(): array
    {
        $codes = $this->roles
            ->map(static function (Role $role): string {
                $code = strtoupper($role->getCodeRole());

                return str_starts_with($code, 'ROLE_') ? $code : 'ROLE_'.$code;
            })
            ->toArray();

        $codes[] = 'ROLE_USER';

        return array_values(array_unique($codes));
    }

    /**
     * Mot de passe haché, lu par le hasher de Symfony.
     */
    public function getPassword(): string
    {
        return $this->motPass;
    }

    public function setPassword(string $motPassHache): static
    {
        $this->motPass = $motPassHache;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Aucune donnée sensible temporaire à effacer (le mot de passe en clair n'est jamais stocké).
    }
}
