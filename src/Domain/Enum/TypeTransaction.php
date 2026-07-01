<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TypeTransaction: string
{
    case VENTE = 'VENTE';
    case RETOUR = 'RETOUR';
    case ANNULATION = 'ANNULATION';

    public function libelle(): string
    {
        return match ($this) {
            self::VENTE => 'Vente',
            self::RETOUR => 'Retour',
            self::ANNULATION => 'Annulation',
        };
    }

    /**
     * Indique si le type impacte le chiffre d'affaires positivement.
     */
    public function estCredit(): bool
    {
        return self::VENTE === $this;
    }
}
