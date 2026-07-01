<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Montant;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * Mappe le value object Montant vers une colonne DECIMAL.
 * Enregistré sous le nom "montant" dans config/packages/doctrine.yaml.
 */
final class MontantType extends Type
{
    public const NAME = 'montant';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['precision'] ??= 10;
        $column['scale'] ??= 2;

        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Montant
    {
        if (null === $value || $value instanceof Montant) {
            return $value;
        }

        return Montant::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Montant) {
            return $value->toDecimal();
        }

        if (\is_string($value) || \is_int($value) || \is_float($value)) {
            return Montant::fromString((string) $value)->toDecimal();
        }

        throw InvalidType::new($value, self::NAME, ['null', 'numeric', Montant::class]);
    }
}
