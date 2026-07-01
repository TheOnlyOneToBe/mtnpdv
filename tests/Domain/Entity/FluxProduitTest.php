<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\Produit;
use App\Domain\ValueObject\Montant;
use PHPUnit\Framework\TestCase;

final class FluxProduitTest extends TestCase
{
    public function testSousTotalCalculeALaConstruction(): void
    {
        $ligne = new FluxProduit(
            new FluxRavitaillement('FACT-100'),
            new Produit('Recharge', 'TELECOM', Montant::fromString('1000.00')),
            7,
            Montant::fromString('950.00'),
        );

        self::assertSame('6650.00', $ligne->getSousTotal()->toDecimal());
    }

    public function testQuantiteNulleRejetee(): void
    {
        $this->expectException(\DomainException::class);

        new FluxProduit(
            new FluxRavitaillement('FACT-101'),
            new Produit('Recharge', 'TELECOM', Montant::fromString('1000.00')),
            0,
            Montant::fromString('1000.00'),
        );
    }

    public function testChangerQuantiteNegativeRejetee(): void
    {
        $ligne = new FluxProduit(
            new FluxRavitaillement('FACT-102'),
            new Produit('Recharge', 'TELECOM', Montant::fromString('1000.00')),
            1,
            Montant::fromString('1000.00'),
        );

        $this->expectException(\DomainException::class);

        $ligne->changerQuantite(-3);
    }
}
