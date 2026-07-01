<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum StatutFlux: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case VALIDE = 'VALIDE';
    case EXPEDIE = 'EXPEDIE';
    case LIVRE = 'LIVRE';
    case ANNULE = 'ANNULE';

    public function libelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDE => 'Validé',
            self::EXPEDIE => 'Expédié',
            self::LIVRE => 'Livré',
            self::ANNULE => 'Annulé',
        };
    }

    public function estTermine(): bool
    {
        return in_array($this, [self::LIVRE, self::ANNULE], true);
    }

    /**
     * Transitions autorisées depuis le statut courant.
     *
     * @return list<self>
     */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::EN_ATTENTE => [self::VALIDE, self::ANNULE],
            self::VALIDE => [self::EXPEDIE, self::ANNULE],
            self::EXPEDIE => [self::LIVRE, self::ANNULE],
            self::LIVRE, self::ANNULE => [],
        };
    }
}
