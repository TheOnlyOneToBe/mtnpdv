<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\TypeNotification;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['utilisateur_id', 'lu'])]
#[ORM\Index(columns: ['date_creation'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: 'string', enumType: TypeNotification::class)]
    private TypeNotification $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lien = null;

    #[ORM\Column(type: 'boolean')]
    private bool $lu = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateLecture = null;

    public function __construct(
        Utilisateur $utilisateur,
        TypeNotification $type,
        string $titre,
        string $message,
        ?string $lien = null,
    ) {
        $this->utilisateur = $utilisateur;
        $this->type = $type;
        $this->titre = $titre;
        $this->message = $message;
        $this->lien = $lien;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getType(): TypeNotification
    {
        return $this->type;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function marquerCommeLue(): void
    {
        $this->lu = true;
        $this->dateLecture = new \DateTimeImmutable();
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateLecture(): ?\DateTimeImmutable
    {
        return $this->dateLecture;
    }
}
