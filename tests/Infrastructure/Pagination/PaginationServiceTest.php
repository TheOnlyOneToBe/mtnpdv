<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Pagination;

use App\Infrastructure\Pagination\PaginationService;
use PHPUnit\Framework\TestCase;

final class PaginationServiceTest extends TestCase
{
    private PaginationService $paginationService;

    protected function setUp(): void
    {
        $this->paginationService = new PaginationService();
    }

    public function testPaginationAvecMoinsD12Elements(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 5));

        $result = $this->paginationService->paginate($items, 1, 12);

        self::assertSame(1, $result['currentPage']);
        self::assertSame(1, $result['totalPages']);
        self::assertSame(5, $result['totalItems']);
        self::assertCount(5, $result['items']);
        self::assertFalse($result['hasNextPage']);
        self::assertFalse($result['hasPreviousPage']);
    }

    public function testPaginationAvec12Elements(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 12));

        $result = $this->paginationService->paginate($items, 1, 12);

        self::assertSame(1, $result['currentPage']);
        self::assertSame(1, $result['totalPages']);
        self::assertSame(12, $result['totalItems']);
        self::assertCount(12, $result['items']);
        self::assertFalse($result['hasNextPage']);
        self::assertFalse($result['hasPreviousPage']);
    }

    public function testPaginationAvec25Elements(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 25));

        $result = $this->paginationService->paginate($items, 1, 12);

        self::assertSame(1, $result['currentPage']);
        self::assertSame(3, $result['totalPages']);
        self::assertSame(25, $result['totalItems']);
        self::assertCount(12, $result['items']);
        self::assertTrue($result['hasNextPage']);
        self::assertFalse($result['hasPreviousPage']);
    }

    public function testDeuxiemePage(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 25));

        $result = $this->paginationService->paginate($items, 2, 12);

        self::assertSame(2, $result['currentPage']);
        self::assertSame(3, $result['totalPages']);
        self::assertCount(12, $result['items']);
        self::assertTrue($result['hasNextPage']);
        self::assertTrue($result['hasPreviousPage']);
        self::assertSame('Item 13', $result['items'][0]);
    }

    public function testDernierePageIncomplete(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 25));

        $result = $this->paginationService->paginate($items, 3, 12);

        self::assertSame(3, $result['currentPage']);
        self::assertSame(3, $result['totalPages']);
        self::assertCount(1, $result['items']);
        self::assertFalse($result['hasNextPage']);
        self::assertTrue($result['hasPreviousPage']);
        self::assertSame('Item 25', $result['items'][0]);
    }

    public function testItemRangeDisplay(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 100));

        $result = $this->paginationService->paginate($items, 2, 12);
        $range = $this->paginationService->getItemRange($result);

        self::assertSame(13, $range['start']);
        self::assertSame(24, $range['end']);
    }

    public function testPaginationMetadata(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 50));

        $result = $this->paginationService->paginate($items, 1, 12);
        $metadata = $this->paginationService->getPageMetadata($result);

        self::assertArrayHasKey('pageNumbers', $metadata);
        self::assertIsArray($metadata['pageNumbers']);
    }

    public function testPageInvalideRetournePageUn(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 25));

        $result = $this->paginationService->paginate($items, 0, 12);

        self::assertSame(1, $result['currentPage']);
    }

    public function testPageAuDelaDuMaxRetourneDernierePage(): void
    {
        $items = array_map(fn ($i) => "Item {$i}", range(1, 25));

        $result = $this->paginationService->paginate($items, 100, 12);

        self::assertSame(3, $result['currentPage']);
    }
}
