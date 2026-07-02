<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Numéro de téléphone normalisé : chiffres uniquement, avec un éventuel "+" initial.
 */
final class Telephone implements \Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalise = preg_replace('/[\s.\-()]/', '', trim($value)) ?? '';

        if (1 !== preg_match('/^\+?[0-9]{8,15}$/', $normalise)) {
            throw new InvalidArgumentException(sprintf('Numéro de téléphone invalide : "%s".', $value));
        }

        $this->value = $normalise;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Alias de value(), utilisé par les templates (telephone.toString()).
     */
    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $autre): bool
    {
        return $this->value === $autre->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
