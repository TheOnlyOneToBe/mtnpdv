<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class SearchRateLimiter
{
    private RateLimiterFactory $factory;

    public function __construct()
    {
        // Configure rate limiter: 60 requests per minute per user
        $this->factory = new RateLimiterFactory([
            'id' => 'search',
            'policy' => 'sliding_window',
            'limit' => 60,
            'interval' => '1 minute',
        ], new InMemoryStorage());
    }

    public function isLimited(string $identifier): bool
    {
        $limiter = $this->factory->create($identifier);
        $limit = $limiter->consume(1);
        return !$limit->isAccepted();
    }
}
