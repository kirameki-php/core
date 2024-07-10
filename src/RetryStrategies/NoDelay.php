<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

class NoDelay implements RetryStrategy
{
    /**
     * @param int $attempt
     * @return int
     */
    public function calculateDelayMicroSeconds(int $attempt): int
    {
        return 0;
    }
}

