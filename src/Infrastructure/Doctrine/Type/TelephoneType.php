<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Telephone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

/**
 * Mappe le value object Telephone vers une colonne VARCHAR.
 * Enregistré sous le nom "telephone" dans config/packages/doctrine.yaml.
 */
final class TelephoneType extends StringType
{
    public const NAME = 'telephone';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Telephone
    {
        if (null === $value || $value instanceof Telephone) {
            return $value;
        }

        return Telephone::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Telephone) {
            return $value->value();
        }

        if (\is_string($value)) {
            return Telephone::fromString($value)->value();
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', Telephone::class]);
    }
}
