<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\PointVente\CodeRefDejaUtiliseException;
use App\Application\PointVente\EnregistrerPointVenteCommande;
use App\Application\PointVente\EnregistrerPointVenteHandler;
use App\Domain\Entity\CategoriePdv;
use App\Domain\Entity\PointVente;
use App\Domain\Enum\StatutPointVente;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\TestCase;

final class EnregistrerPointVenteHandlerTest extends TestCase
{
    private function creerCommande(): EnregistrerPointVenteCommande
    {
        return new EnregistrerPointVenteCommande(
            'Kiosque Bonanjo',
            'PDV-042',
            new Coordonnees(4.0446, 9.6929),
            'Douala',
            Telephone::fromString('+237690555555'),
            adresse: 'Rue Joffre',
            categorie: new CategoriePdv('Kiosque'),
        );
    }

    public function testCreationReussie(): void
    {
        $repo = $this->createMock(PointVenteRepositoryInterface::class);
        $repo->expects(self::once())->method('findOneByCodeRef')->with('PDV-042')->willReturn(null);
        $repo->expects(self::once())->method('save')->with(self::isInstanceOf(PointVente::class));

        $pointVente = (new EnregistrerPointVenteHandler($repo))($this->creerCommande());

        self::assertSame('Kiosque Bonanjo', $pointVente->getNomPdv());
        self::assertSame('PDV-042', $pointVente->getCodeRef());
        self::assertSame(StatutPointVente::ACTIF, $pointVente->getStatutActuel());
        self::assertSame('Rue Joffre', $pointVente->getAdresse());
        self::assertSame('Kiosque', $pointVente->getCategoriePdv()?->getLibelleCatpdv());
        self::assertNull($pointVente->getGerant());
    }

    public function testCodeRefDupliqueRejeteSansPersister(): void
    {
        $existant = new PointVente(
            'Autre kiosque',
            'PDV-042',
            new Coordonnees(0, 0),
            'Yaoundé',
            Telephone::fromString('+237690666666'),
        );

        $repo = $this->createMock(PointVenteRepositoryInterface::class);
        $repo->expects(self::once())->method('findOneByCodeRef')->with('PDV-042')->willReturn($existant);
        $repo->expects(self::never())->method('save');

        $this->expectException(CodeRefDejaUtiliseException::class);

        (new EnregistrerPointVenteHandler($repo))($this->creerCommande());
    }
}
