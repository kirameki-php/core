<?php declare(strict_types=1);

namespace Kirameki\Core\Retry;

interface RetryPolicy
{
    /**
     * @param int $attempt
     * @return int
     */
    public function calculateDelayMilliseconds(int $attempt): int;

    /**
     * @return void
     */
    public function reset(): void;
}
