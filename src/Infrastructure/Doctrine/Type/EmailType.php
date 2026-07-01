<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

/**
 * Mappe le value object Email vers une colonne VARCHAR.
 * Enregistré sous le nom "email" dans config/packages/doctrine.yaml.
 */
final class EmailType extends StringType
{
    public const NAME = 'email';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        if (null === $value || $value instanceof Email) {
            return $value;
        }

        return Email::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Email) {
            return $value->value();
        }

        if (\is_string($value)) {
            return Email::fromString($value)->value();
        }

        throw InvalidType::new($value, self::NAME, ['null', 'string', Email::class]);
    }
}
