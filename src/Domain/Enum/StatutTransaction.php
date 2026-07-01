<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatutTransaction: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDEE = 'VALIDEE';
    case REJETEE = 'REJETEE';
    case ANNULEE = 'ANNULEE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validée',
            self::REJETEE => 'Rejetée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function estFinal(): bool
    {
        return self::EN_ATTENTE !== $this;
    }
}
