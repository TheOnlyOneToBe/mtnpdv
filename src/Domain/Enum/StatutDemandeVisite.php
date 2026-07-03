<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Statuts d'une demande de visite.
 *
 * Workflow:
 * DEMANDEE → ASSIGNEE → EFFECTUEE → VALIDEE/REJETEE
 *
 * Ou:
 * DEMANDEE → ASSIGNEE → ANNULEE
 */
enum StatutDemandeVisite: string
{
    case DEMANDEE = 'DEMANDEE';      // Admin a créé la demande, en attente d'assignation
    case ASSIGNEE = 'ASSIGNEE';      // Agent assigné à la demande
    case EFFECTUEE = 'EFFECTUEE';    // Agent a exécuté la visite (Transaction créée)
    case VALIDEE = 'VALIDEE';        // Admin a validé la visite
    case REJETEE = 'REJETEE';        // Admin a rejeté la visite
    case ANNULEE = 'ANNULEE';        // Demande annulée (avant exécution)

    public function estPendante(): bool
    {
        return $this === self::DEMANDEE || $this === self::ASSIGNEE;
    }

    public function estFinalisee(): bool
    {
        return $this === self::VALIDEE || $this === self::REJETEE || $this === self::ANNULEE;
    }

    public function estExecutee(): bool
    {
        return $this === self::EFFECTUEE || $this === self::VALIDEE || $this === self::REJETEE;
    }

    public function libelle(): string
    {
        return match ($this) {
            self::DEMANDEE => 'Demandée',
            self::ASSIGNEE => 'Assignée',
            self::EFFECTUEE => 'Effectuée',
            self::VALIDEE => 'Validée',
            self::REJETEE => 'Rejetée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::DEMANDEE => 'info',
            self::ASSIGNEE => 'warning',
            self::EFFECTUEE => 'primary',
            self::VALIDEE => 'success',
            self::REJETEE => 'danger',
            self::ANNULEE => 'secondary',
        };
    }
}
