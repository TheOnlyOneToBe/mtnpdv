<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Montant monétaire à deux décimales, stocké en centimes pour éviter
 * les erreurs d'arrondi des flottants. Correspond à DECIMAL(10,2) en base.
 */
final class Montant implements \Stringable
{
    private function __construct(
        private readonly int $centimes,
    ) {
    }

    /**
     * @param string $value montant décimal, ex. "1250.50"
     */
    public static function fromString(string $value): self
    {
        $normalise = str_replace(',', '.', trim($value));

        if (1 !== preg_match('/^-?[0-9]+(\.[0-9]{1,2})?$/', $normalise)) {
            throw new InvalidArgumentException(sprintf('Montant invalide : "%s".', $value));
        }

        [$entier, $decimales] = array_pad(explode('.', ltrim($normalise, '-')), 2, '0');
        $centimes = ((int) $entier) * 100 + (int) str_pad($decimales, 2, '0');

        return new self(str_starts_with($normalise, '-') ? -$centimes : $centimes);
    }

    public static function fromCentimes(int $centimes): self
    {
        return new self($centimes);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function ajouter(self $autre): self
    {
        return new self($this->centimes + $autre->centimes);
    }

    public function soustraire(self $autre): self
    {
        return new self($this->centimes - $autre->centimes);
    }

    public function multiplier(int $quantite): self
    {
        return new self($this->centimes * $quantite);
    }

    public function centimes(): int
    {
        return $this->centimes;
    }

    /**
     * Alias de centimes(), utilisé par les templates et contrôleurs.
     */
    public function montantCentimes(): int
    {
        return $this->centimes;
    }

    public function estPositif(): bool
    {
        return $this->centimes > 0;
    }

    public function equals(self $autre): bool
    {
        return $this->centimes === $autre->centimes;
    }

    public function compare(self $autre): int
    {
        return $this->centimes <=> $autre->centimes;
    }

    /**
     * Représentation décimale pour la base de données, ex. "1250.50".
     */
    public function toDecimal(): string
    {
        $absolu = abs($this->centimes);

        return sprintf('%s%d.%02d', $this->centimes < 0 ? '-' : '', intdiv($absolu, 100), $absolu % 100);
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }
}
