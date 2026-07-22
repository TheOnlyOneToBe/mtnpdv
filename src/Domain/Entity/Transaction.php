<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeProblemeSupervision;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use App\Infrastructure\Doctrine\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: '`transaction`')]
#[ORM\Index(name: 'IDX_TRANSACTION_DATE', columns: ['date_transac'])]
#[Uploadable]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'date_transac', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateTransac;

    #[ORM\Column(name: 'commentaire_rapport', type: Types::TEXT, nullable: true)]
    private ?string $commentaireRapport = null;

    #[UploadableField(mapping: 'photos_visite', fileNameProperty: 'photoPreuveUrl')]
    private ?File $photoFile = null;

    #[ORM\Column(name: 'photo_preuve_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $photoPreuveUrl = null;

    /**
     * Position GPS capturée au moment de la transaction
     * (colonnes latitude_capture / longitude_capture).
     */
    #[ORM\Column(name: 'latitude_capture', type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $latitudeCapture;

    #[ORM\Column(name: 'longitude_capture', type: Types::DECIMAL, precision: 11, scale: 8)]
    private string $longitudeCapture;

    #[ORM\Column(name: 'type_enum', type: Types::STRING, length: 50, enumType: TypeTransaction::class)]
    private TypeTransaction $type;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: StatutTransaction::class, options: ['default' => 'EN_ATTENTE'])]
    private StatutTransaction $statut = StatutTransaction::EN_ATTENTE;

    #[ORM\Column(type: 'montant', precision: 10, scale: 2)]
    private Montant $montant;

    #[ORM\Column(name: 'type_probleme', type: Types::STRING, length: 100, enumType: TypeProblemeSupervision::class, nullable: true)]
    private ?TypeProblemeSupervision $typeProbleme = null;

    #[ORM\ManyToOne(targetEntity: PointVente::class)]
    #[ORM\JoinColumn(name: 'point_vente_id', onDelete: 'SET NULL')]
    private ?PointVente $pointVente = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', onDelete: 'SET NULL')]
    private ?Utilisateur $utilisateur = null;

    public function __construct(
        TypeTransaction $type,
        Montant $montant,
        Coordonnees $coordonneesCapture,
    ) {
        $this->type = $type;
        $this->montant = $montant;
        $this->latitudeCapture = number_format($coordonneesCapture->latitude(), 8, '.', '');
        $this->longitudeCapture = number_format($coordonneesCapture->longitude(), 8, '.', '');
        $this->dateTransac = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateTransac(): \DateTimeImmutable
    {
        return $this->dateTransac;
    }

    public function getCommentaireRapport(): ?string
    {
        return $this->commentaireRapport;
    }

    public function setCommentaireRapport(?string $commentaireRapport): static
    {
        $this->commentaireRapport = $commentaireRapport;

        return $this;
    }

    public function getPhotoPreuveUrl(): ?string
    {
        return $this->photoPreuveUrl;
    }

    public function setPhotoPreuveUrl(?string $photoPreuveUrl): static
    {
        $this->photoPreuveUrl = $photoPreuveUrl;

        return $this;
    }

    public function getCoordonneesCapture(): Coordonnees
    {
        return new Coordonnees($this->latitudeCapture, $this->longitudeCapture);
    }

    public function setCoordonneesCapture(Coordonnees $coordonnees): static
    {
        $this->latitudeCapture = number_format($coordonnees->latitude(), 8, '.', '');
        $this->longitudeCapture = number_format($coordonnees->longitude(), 8, '.', '');

        return $this;
    }

    public function getType(): TypeTransaction
    {
        return $this->type;
    }

    public function getStatut(): StatutTransaction
    {
        return $this->statut;
    }

    public function valider(): static
    {
        return $this->changerStatut(StatutTransaction::VALIDEE);
    }

    public function rejeter(): static
    {
        return $this->changerStatut(StatutTransaction::REJETEE);
    }

    public function annuler(): static
    {
        return $this->changerStatut(StatutTransaction::ANNULEE);
    }

    public function confirmerRecu(): static
    {
        return $this->changerStatut(StatutTransaction::RECU_PAR_AGENT);
    }

    public function terminer(): static
    {
        return $this->changerStatut(StatutTransaction::TERMINEE);
    }

    public function getMontant(): Montant
    {
        return $this->montant;
    }

    public function setMontant(Montant $montant): static
    {
        $this->montant = $montant;

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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Alias de getUtilisateur() : l'agent ayant effectué la transaction.
     */
    public function getAgent(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setPhotoFile(?File $photoFile = null): static
    {
        $this->photoFile = $photoFile;

        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function getTypeProbleme(): ?TypeProblemeSupervision
    {
        return $this->typeProbleme;
    }

    public function setTypeProbleme(?TypeProblemeSupervision $typeProbleme): static
    {
        $this->typeProbleme = $typeProbleme;

        return $this;
    }

    /**
     * @throws \DomainException si la transaction a déjà atteint un statut final
     */
    private function changerStatut(StatutTransaction $nouveauStatut): static
    {
        if ($this->statut->estFinal()) {
            throw new \DomainException(sprintf(
                'La transaction est déjà au statut final %s.',
                $this->statut->value,
            ));
        }

        $this->statut = $nouveauStatut;

        return $this;
    }
}
