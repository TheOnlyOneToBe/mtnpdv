<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Coordonnées GPS (latitude/longitude) embarquées dans les entités.
 * Latitude DECIMAL(10,8), longitude DECIMAL(11,8) comme en base.
 */
#[ORM\Embeddable]
final class Coordonnees
{
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8)]
    private string $longitude;

    public function __construct(float|string $latitude, float|string $longitude)
    {
        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -90.0 || $lat > 90.0) {
            throw new InvalidArgumentException(sprintf('Latitude hors limites : %s.', $latitude));
        }

        if ($lng < -180.0 || $lng > 180.0) {
            throw new InvalidArgumentException(sprintf('Longitude hors limites : %s.', $longitude));
        }

        $this->latitude = number_format($lat, 8, '.', '');
        $this->longitude = number_format($lng, 8, '.', '');
    }

    public function latitude(): float
    {
        return (float) $this->latitude;
    }

    public function longitude(): float
    {
        return (float) $this->longitude;
    }

    /**
     * Alias de latitude(), utilisé par les templates et contrôleurs.
     */
    public function getLatitude(): float
    {
        return $this->latitude();
    }

    /**
     * Alias de longitude(), utilisé par les templates et contrôleurs.
     */
    public function getLongitude(): float
    {
        return $this->longitude();
    }

    /**
     * Distance en kilomètres jusqu'à d'autres coordonnées (formule de Haversine).
     */
    public function distanceVers(self $autre): float
    {
        $rayonTerre = 6371.0;

        $dLat = deg2rad($autre->latitude() - $this->latitude());
        $dLng = deg2rad($autre->longitude() - $this->longitude());

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->latitude())) * cos(deg2rad($autre->latitude())) * sin($dLng / 2) ** 2;

        return $rayonTerre * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function equals(self $autre): bool
    {
        return $this->latitude === $autre->latitude && $this->longitude === $autre->longitude;
    }
}
