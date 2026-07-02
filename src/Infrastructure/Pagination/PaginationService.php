<?php

declare(strict_types=1);

namespace App\Infrastructure\Pagination;

class PaginationService
{
    public const DEFAULT_ITEMS_PER_PAGE = 12;
    public const MAX_ITEMS_PER_PAGE = 100;

    /**
     * Paginate an array or collection of items.
     *
     * @param array $items
     * @param int $currentPage
     * @param int $itemsPerPage
     * @return array{items: array, currentPage: int, totalPages: int, totalItems: int, offset: int, limit: int, hasNextPage: bool, hasPreviousPage: bool}
     */
    public function paginate(
        array $items,
        int $currentPage = 1,
        int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
    ): array {
        // Validate and constrain inputs
        $itemsPerPage = min(max(1, $itemsPerPage), self::MAX_ITEMS_PER_PAGE);
        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
        $currentPage = max(1, min($currentPage, $totalPages));

        // Calculate offset
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Slice items for current page
        $paginatedItems = array_slice($items, $offset, $itemsPerPage);

        return [
            'items' => $paginatedItems,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'offset' => $offset,
            'limit' => $itemsPerPage,
            'hasNextPage' => $currentPage < $totalPages,
            'hasPreviousPage' => $currentPage > 1,
        ];
    }

    /**
     * Generate pagination metadata for templates.
     *
     * @param array $paginationData
     * @return array{pages: array, pageNumbers: array}
     */
    public function getPageMetadata(array $paginationData): array
    {
        $currentPage = $paginationData['currentPage'];
        $totalPages = $paginationData['totalPages'];

        // Generate page numbers (show current page +/- 2 pages)
        $pageNumbers = [];
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        if ($startPage > 1) {
            $pageNumbers[] = 1;
            if ($startPage > 2) {
                $pageNumbers[] = null; // Represents ellipsis
            }
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            $pageNumbers[] = $i;
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $pageNumbers[] = null; // Represents ellipsis
            }
            $pageNumbers[] = $totalPages;
        }

        return [
            'pages' => $pageNumbers,
            'pageNumbers' => range(1, $totalPages),
        ];
    }

    /**
     * Format start and end item numbers for display.
     *
     * @param array $paginationData
     * @return array{start: int, end: int}
     */
    public function getItemRange(array $paginationData): array
    {
        $offset = $paginationData['offset'];
        $limit = $paginationData['limit'];
        $totalItems = $paginationData['totalItems'];

        return [
            'start' => $totalItems > 0 ? $offset + 1 : 0,
            'end' => min($offset + $limit, $totalItems),
        ];
    }
}
