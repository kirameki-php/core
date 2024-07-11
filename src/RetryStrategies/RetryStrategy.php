<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

interface RetryStrategy
{
    /**
     * @param int $attempt
     * @return int
     */
    public function calculateDelayMilliSeconds(int $attempt): int;

    /**
     * @return void
     */
    public function reset(): void;
}

