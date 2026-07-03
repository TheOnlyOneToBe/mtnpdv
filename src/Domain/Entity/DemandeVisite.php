<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatutDemandeVisite;
use App\Infrastructure\Doctrine\Repository\DemandeVisiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Demande de visite créée par l'administrateur.
 *
 * Workflow:
 * 1. Admin crée une demande pour un PDV spécifique
 * 2. Admin assigne l'agent responsable
 * 3. Agent exécute la visite (crée une Transaction)
 * 4. Admin valide ou rejette la visite
 *
 * Une demande lie un PointVente à un Utilisateur (agent) avec un contexte
 * (raison, date demandée, notes).
 */
#[ORM\Entity(repositoryClass: DemandeVisiteRepository::class)]
#[ORM\Table(name: 'demande_visite')]
#[ORM\Index(name: 'IDX_DEMANDE_VISITE_PDV', columns: ['point_vente_id'])]
#[ORM\Index(name: 'IDX_DEMANDE_VISITE_AGENT', columns: ['agent_id'])]
#[ORM\Index(name: 'IDX_DEMANDE_VISITE_STATUT', columns: ['statut'])]
class DemandeVisite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PointVente::class)]
    #[ORM\JoinColumn(name: 'point_vente_id', onDelete: 'CASCADE')]
    private PointVente $pointVente;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'agent_id', nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $agent = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'admin_id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $administrateur;

    #[ORM\ManyToOne(targetEntity: Transaction::class)]
    #[ORM\JoinColumn(name: 'transaction_id', nullable: true, onDelete: 'SET NULL')]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $motif;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_demandee', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateDemandee;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'date_effectuee', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateEffectuee = null;

    #[ORM\Column(name: 'date_validee', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValidee = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: StatutDemandeVisite::class)]
    private StatutDemandeVisite $statut = StatutDemandeVisite::DEMANDEE;

    #[ORM\Column(name: 'motif_rejet', type: Types::TEXT, nullable: true)]
    private ?string $motifRejet = null;

    public function __construct(
        PointVente $pointVente,
        Utilisateur $administrateur,
        string $motif,
        \DateTimeImmutable $dateDemandee,
    ) {
        $this->pointVente = $pointVente;
        $this->administrateur = $administrateur;
        $this->motif = $motif;
        $this->dateDemandee = $dateDemandee;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPointVente(): PointVente
    {
        return $this->pointVente;
    }

    public function getAgent(): ?Utilisateur
    {
        return $this->agent;
    }

    public function setAgent(?Utilisateur $agent): static
    {
        $this->agent = $agent;

        if (null !== $agent) {
            $this->changerStatut(StatutDemandeVisite::ASSIGNEE);
        }

        return $this;
    }

    public function getAdministrateur(): Utilisateur
    {
        return $this->administrateur;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;

        if (null !== $transaction) {
            $this->dateEffectuee = new \DateTimeImmutable();
            $this->changerStatut(StatutDemandeVisite::EFFECTUEE);
        }

        return $this;
    }

    public function getMotif(): string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDemandee(): \DateTimeImmutable
    {
        return $this->dateDemandee;
    }

    public function setDateDemandee(\DateTimeImmutable $dateDemandee): static
    {
        $this->dateDemandee = $dateDemandee;

        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateEffectuee(): ?\DateTimeImmutable
    {
        return $this->dateEffectuee;
    }

    public function getDateValidee(): ?\DateTimeImmutable
    {
        return $this->dateValidee;
    }

    public function getStatut(): StatutDemandeVisite
    {
        return $this->statut;
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

        return $this;
    }

    /**
     * Change le statut de la demande.
     * Respecte le workflow: DEMANDEE → ASSIGNEE → EFFECTUEE → VALIDEE/REJETEE
     */
    public function changerStatut(StatutDemandeVisite $nouveauStatut): static
    {
        // Vérifier que la transition est autorisée
        $transitionsAutorisees = match ($this->statut) {
            StatutDemandeVisite::DEMANDEE => [
                StatutDemandeVisite::ASSIGNEE,
                StatutDemandeVisite::ANNULEE,
            ],
            StatutDemandeVisite::ASSIGNEE => [
                StatutDemandeVisite::EFFECTUEE,
                StatutDemandeVisite::ANNULEE,
            ],
            StatutDemandeVisite::EFFECTUEE => [
                StatutDemandeVisite::VALIDEE,
                StatutDemandeVisite::REJETEE,
            ],
            StatutDemandeVisite::VALIDEE,
            StatutDemandeVisite::REJETEE,
            StatutDemandeVisite::ANNULEE => [],
        };

        if (!in_array($nouveauStatut, $transitionsAutorisees, true)) {
            throw new \DomainException(sprintf(
                'Transition de statut non autorisée: %s → %s',
                $this->statut->value,
                $nouveauStatut->value,
            ));
        }

        $this->statut = $nouveauStatut;

        if ($nouveauStatut === StatutDemandeVisite::VALIDEE) {
            $this->dateValidee = new \DateTimeImmutable();
        }

        return $this;
    }

    public function valider(): static
    {
        return $this->changerStatut(StatutDemandeVisite::VALIDEE);
    }

    public function rejeter(string $motif): static
    {
        $this->motifRejet = $motif;

        return $this->changerStatut(StatutDemandeVisite::REJETEE);
    }

    public function annuler(): static
    {
        return $this->changerStatut(StatutDemandeVisite::ANNULEE);
    }

    public function estEffectuee(): bool
    {
        return $this->statut === StatutDemandeVisite::EFFECTUEE
            || $this->statut === StatutDemandeVisite::VALIDEE
            || $this->statut === StatutDemandeVisite::REJETEE;
    }

    public function estFinalisee(): bool
    {
        return $this->statut === StatutDemandeVisite::VALIDEE
            || $this->statut === StatutDemandeVisite::REJETEE
            || $this->statut === StatutDemandeVisite::ANNULEE;
    }
}
