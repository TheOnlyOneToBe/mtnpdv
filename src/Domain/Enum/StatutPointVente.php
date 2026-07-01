<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatutPointVente: string
{
    case ACTIF = 'ACTIF';
    case INACTIF = 'INACTIF';
    case SUSPENDU = 'SUSPENDU';
    case FERME = 'FERME';

    public function libelle(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::INACTIF => 'Inactif',
            self::SUSPENDU => 'Suspendu',
            self::FERME => 'Fermé',
        };
    }

    public function estOperationnel(): bool
    {
        return self::ACTIF === $this;
    }
}
