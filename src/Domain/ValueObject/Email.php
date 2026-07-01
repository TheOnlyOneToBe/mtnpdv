<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Adresse e-mail validée et normalisée (minuscules, sans espaces).
 */
final class Email implements \Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalise = mb_strtolower(trim($value));

        if (false === filter_var($normalise, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('Adresse e-mail invalide : "%s".', $value));
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

    public function domaine(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
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
