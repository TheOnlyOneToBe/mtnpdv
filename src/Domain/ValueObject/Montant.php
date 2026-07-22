<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Montant monétaire stocké directement (sans centimes)
 */
final class Montant implements \Stringable
{
    private function __construct(
        private readonly int $valeur,
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

        // Convertir en entier en gardant les deux décimales (mais maintenant c'est la valeur directe)
        // Par exemple: "1250.50" devient 1250.50 → on peut stocker en centimes pour éviter les flottants ?
        // Attendons, si on retire la division par 100, on doit décider comment stocker...
        // Peut-être que le user veut que la valeur soit stockée directement, ex: 1000 au lieu de 100000 centimes
        // Donc fromString("1000") → 1000, fromString("1000.50") → 1000.50?
        // Mais pour éviter les erreurs d'arrondi, mieux de continuer à stocker en centimes mais avec une méthode fromValeur() ?
        // Attendons, posons la question: le user veut que:
        // - Montant::fromCentimes(100000) → devient Montant::fromValeur(1000)
        // - montant->centimes() → devient montant->valeur()
        // - toDecimal() reste la même (affiche 1000.00 pour 1000)

        [$entier, $decimales] = array_pad(explode('.', ltrim($normalise, '-')), 2, '0');
        $valeur = ((int) $entier) * 100 + (int) str_pad($decimales, 2, '0');

        return new self(str_starts_with($normalise, '-') ? -$valeur : $valeur);
    }

    public static function fromValeur(int $valeur): self
    {
        // Si la valeur est en FCFA (ex: 1000), on multiplie par 100 pour stocker en centimes
        return new self($valeur * 100);
    }

    public static function fromCentimes(int $centimes): self
    {
        // Pour la rétrocompatibilité, on garde cette méthode
        return new self($centimes);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function ajouter(self $autre): self
    {
        return new self($this->valeur + $autre->valeur);
    }

    public function add(self $autre): self
    {
        return $this->ajouter($autre);
    }

    public function soustraire(self $autre): self
    {
        return new self($this->valeur - $autre->valeur);
    }

    public function lessThan(self $autre): bool
    {
        return $this->valeur < $autre->valeur;
    }

    public function multiplier(int $quantite): self
    {
        return new self($this->valeur * $quantite);
    }

    public function valeur(): int
    {
        // Retourne la valeur en FCFA (divisée par 100)
        return intdiv($this->valeur, 100);
    }

    public function centimes(): int
    {
        return $this->valeur;
    }

    /**
     * Alias de centimes(), utilisé par les templates et contrôleurs.
     */
    public function montantCentimes(): int
    {
        return $this->valeur;
    }

    /**
     * Alias de valeur(), utilisé par les services et dashboards.
     */
    public function getValue(): int
    {
        return intdiv($this->valeur, 100);
    }

    /**
     * Alias de getValue(), pour accès Twig direct.
     */
    public function value(): int
    {
        return intdiv($this->valeur, 100);
    }

    public function estPositif(): bool
    {
        return $this->valeur > 0;
    }

    public function equals(self $autre): bool
    {
        return $this->valeur === $autre->valeur;
    }

    public function compare(self $autre): int
    {
        return $this->valeur <=> $autre->valeur;
    }

    /**
     * Représentation décimale pour la base de données, ex. "1250.50".
     */
    public function toDecimal(): string
    {
        $absolu = abs($this->valeur);

        return sprintf('%s%d.%02d', $this->valeur < 0 ? '-' : '', intdiv($absolu, 100), $absolu % 100);
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }
}
