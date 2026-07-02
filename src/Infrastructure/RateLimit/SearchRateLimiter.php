<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\RateLimiter\RateLimiter;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\RateLimiter\Strategy\SlidingWindowStrategy;

class SearchRateLimiter
{
    private RateLimiter $limiter;

    public function __construct()
    {
        // Use in-memory cache for rate limiting
        $storage = new CacheStorage(new ArrayAdapter());
        $this->limiter = new RateLimiter(
            new SlidingWindowStrategy(60, \DateInterval::createFromDateString('1 minute')),
            $storage,
        );
    }

    public function isLimited(string $identifier): bool
    {
        $limit = $this->limiter->consume(1, $identifier);
        return !$limit->isAccepted();
    }
}
