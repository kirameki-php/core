<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

use function random_int;

class ExponentialBackoff implements RetryStrategy
{
    /**
     * @param int $baseDelayMicroSeconds
     * @param int $maxDelayMicroSeconds
     * @param bool $jitter
     */
    public function __construct(
        protected int $baseDelayMicroSeconds = 10_000,
        protected int $maxDelayMicroSeconds = 100_000,
        protected bool $jitter = true,
    )
    {
    }

    /**
     * @param int $attempt
     * @return int
     */
    public function calculateDelayMicroSeconds(int $attempt): int
    {
        $usleep = $this->baseDelayMicroSeconds * (2 ** $attempt);
        $usleep = min($usleep, $this->maxDelayMicroSeconds);
        return $this->addJitter($usleep);
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function addJitter(int $delay): int
    {
        return $this->jitter ? random_int(0, $delay) : $delay;
    }
}

