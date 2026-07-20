<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Domain\ValueObject\Montant;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final readonly class MontantToNumberTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?float
    {
        if (!$value instanceof Montant) {
            return null;
        }

        return (float) $value->toDecimal();
    }

    public function reverseTransform(mixed $value): ?Montant
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric value for amount.');
        }

        return Montant::fromCentimes((int) round((float) $value * 100));
    }
}
