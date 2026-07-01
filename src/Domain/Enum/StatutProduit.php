<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatutProduit: int
{
    case INACTIF = 0;
    case ACTIF = 1;

    public function libelle(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::INACTIF => 'Inactif',
        };
    }

    public function estActif(): bool
    {
        return self::ACTIF === $this;
    }
}
