<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalisationMinusculesEtEspaces(): void
    {
        $email = Email::fromString('  Jean.DUPONT@Example.COM ');

        self::assertSame('jean.dupont@example.com', $email->value());
        self::assertSame('example.com', $email->domaine());
        self::assertSame('jean.dupont@example.com', (string) $email);
    }

    public function testEgalite(): void
    {
        self::assertTrue(Email::fromString('a@b.com')->equals(Email::fromString('A@B.COM')));
        self::assertFalse(Email::fromString('a@b.com')->equals(Email::fromString('c@b.com')));
    }

    #[DataProvider('provideEmailsInvalides')]
    public function testEmailInvalideRejete(string $entree): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::fromString($entree);
    }

    /** @return iterable<array{string}> */
    public static function provideEmailsInvalides(): iterable
    {
        yield 'sans arobase' => ['pas-un-email'];
        yield 'sans domaine' => ['jean@'];
        yield 'vide' => [''];
        yield 'espaces internes' => ['jean dupont@exemple.com'];
    }
}
