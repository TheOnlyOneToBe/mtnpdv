<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatutTransaction: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case RECU_PAR_AGENT = 'RECU_PAR_AGENT';
    case VALIDEE = 'VALIDEE';
    case REJETEE = 'REJETEE';
    case ANNULEE = 'ANNULEE';
    case TERMINEE = 'TERMINEE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::RECU_PAR_AGENT => 'Reçu par l\'agent',
            self::VALIDEE => 'Validée',
            self::REJETEE => 'Rejetée',
            self::ANNULEE => 'Annulée',
            self::TERMINEE => 'Terminée',
        };
    }

    public function estFinal(): bool
    {
        return in_array($this, [self::VALIDEE, self::REJETEE, self::ANNULEE, self::TERMINEE], true);
    }
}
