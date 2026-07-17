<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * Statuts d'une demande de visite/mission.
 *
 * Workflow:
 * DEMANDEE → ASSIGNEE → ACCEPTEE → EFFECTUEE → VALIDEE/REJETEE
 *
 * Ou:
 * DEMANDEE → ASSIGNEE → ANNULEE
 * Ou:
 * ACCEPTEE → ANNULEE
 */
enum StatutDemandeVisite: string
{
    case DEMANDEE = 'DEMANDEE';      // Demande créée, en attente d'assignation
    case ASSIGNEE = 'ASSIGNEE';      // Agent assigné, en attente d'acceptation
    case ACCEPTEE = 'ACCEPTEE';      // Agent a accepté la mission
    case EFFECTUEE = 'EFFECTUEE';    // Agent a exécuté la mission (Transaction créée)
    case VALIDEE = 'VALIDEE';        // Admin a validé la mission
    case REJETEE = 'REJETEE';        // Admin a rejeté la mission
    case ANNULEE = 'ANNULEE';        // Demande annulée

    public function estPendante(): bool
    {
        return $this === self::DEMANDEE || $this === self::ASSIGNEE || $this === self::ACCEPTEE;
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
            self::ACCEPTEE => 'Acceptée',
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
            self::ACCEPTEE => 'primary',
            self::EFFECTUEE => 'info',
            self::VALIDEE => 'success',
            self::REJETEE => 'danger',
            self::ANNULEE => 'secondary',
        };
    }
}
