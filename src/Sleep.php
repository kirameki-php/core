<?php declare(strict_types=1);

namespace Kirameki\Core;

use DateTimeInterface;
use function microtime;
use function usleep;

class Sleep
{
    /**
     * @param int $duration
     */
    public function microseconds(int $duration): void
    {
        if ($duration > 0) {
            usleep($duration);
        }
    }

    /**
     * @param int $duration
     */
    public function milliseconds(int $duration): void
    {
        $this->microseconds($duration * 1_000);
    }

    /**
     * @param int $duration
     */
    public function seconds(int $duration): void
    {
        $this->microseconds($duration * 1_000_000);
    }

    /**
     * @param DateTimeInterface $time
     */
    public function until(DateTimeInterface $time): void
    {
        $thenSeconds = (float) $time->format('U.u');
        $nowSeconds = microtime(true);
        $diffMicroSeconds = ($thenSeconds - $nowSeconds) * 1_000_000;
        $this->microseconds((int) $diffMicroSeconds);
    }
}
