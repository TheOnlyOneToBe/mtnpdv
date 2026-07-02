<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Pagination\PaginationService;
use PHPUnit\Framework\TestCase;

final class PaginationServiceFeatureTest extends TestCase
{
    private PaginationService $service;

    protected function setUp(): void
    {
        $this->service = new PaginationService();
    }

    public function testPaginationBasique(): void
    {
        $items = range(1, 50);
        $result = $this->service->paginate($items);

        self::assertSame(12, count($result['items'])); // Default 12 per page
        self::assertSame(1, $result['currentPage']);
        self::assertSame(5, $result['totalPages']); // 50 items / 12 = 4.16... = 5 pages
        self::assertSame(50, $result['totalItems']);
    }

    public function testCalculOffset(): void
    {
        $items = range(1, 100);
        $result = $this->service->paginate($items, 3, 12);

        self::assertSame(24, $result['offset']); // Page 3: (3-1) * 12 = 24
    }

    public function testNextPageDetection(): void
    {
        $items = range(1, 50);

        $page1 = $this->service->paginate($items, 1, 12);
        self::assertTrue($page1['hasNextPage']);

        $page5 = $this->service->paginate($items, 5, 12);
        self::assertFalse($page5['hasNextPage']);
    }

    public function testPreviousPageDetection(): void
    {
        $items = range(1, 50);

        $page1 = $this->service->paginate($items, 1, 12);
        self::assertFalse($page1['hasPreviousPage']);

        $page3 = $this->service->paginate($items, 3, 12);
        self::assertTrue($page3['hasPreviousPage']);
    }

    public function testInvalidPageReturnsFirstPage(): void
    {
        $items = range(1, 50);

        $result = $this->service->paginate($items, 0, 12);
        self::assertSame(1, $result['currentPage']);

        $result = $this->service->paginate($items, -5, 12);
        self::assertSame(1, $result['currentPage']);
    }

    public function testExcessivePageReturnLastPage(): void
    {
        $items = range(1, 50);

        $result = $this->service->paginate($items, 100, 12);
        self::assertSame(5, $result['currentPage']);
    }

    public function testPageMetadataGeneration(): void
    {
        $items = range(1, 100);
        $result = $this->service->paginate($items, 5, 10);
        $metadata = $this->service->getPageMetadata($result);

        self::assertArrayHasKey('pageNumbers', $metadata);
        self::assertArrayHasKey('pages', $metadata);
        self::assertIsArray($metadata['pageNumbers']);
        self::assertIsArray($metadata['pages']);
    }

    public function testItemRangeCalculation(): void
    {
        $items = range(1, 100);
        $result = $this->service->paginate($items, 2, 12);
        $range = $this->service->getItemRange($result);

        self::assertSame(13, $range['start']); // Page 2 starts at item 13
        self::assertSame(24, $range['end']);   // and ends at 24
    }

    public function testEmptyCollectionPagination(): void
    {
        $result = $this->service->paginate([], 1, 12);

        self::assertSame(1, $result['currentPage']);
        self::assertSame(1, $result['totalPages']);
        self::assertSame(0, $result['totalItems']);
        self::assertCount(0, $result['items']);
        self::assertFalse($result['hasNextPage']);
        self::assertFalse($result['hasPreviousPage']);
    }

    public function testCustomItemsPerPage(): void
    {
        $items = range(1, 50);
        $result = $this->service->paginate($items, 1, 25);

        self::assertSame(25, count($result['items']));
        self::assertSame(2, $result['totalPages']);
    }

    public function testLimitMaxItemsPerPage(): void
    {
        $items = range(1, 10000);
        $result = $this->service->paginate($items, 1, 9999);

        // MAX_ITEMS_PER_PAGE = 100
        self::assertSame(100, count($result['items']));
    }
}
