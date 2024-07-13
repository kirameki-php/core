<?php declare(strict_types=1);

namespace Kirameki\Core;

use DateTimeInterface;
use function microtime;
use function usleep;

class Sleep
{
    /**
     * @param int $amount
     */
    public function microseconds(int $amount): void
    {
        if ($amount > 0) {
            usleep($amount);
        }
    }

    /**
     * @param int $amount
     */
    public function milliseconds(int $amount): void
    {
        $this->microseconds($amount * 1_000);
    }

    /**
     * @param int $amount
     */
    public function seconds(int $amount): void
    {
        $this->microseconds($amount * 1_000_000);
    }

    /**
     * @param DateTimeInterface $then
     */
    public function until(DateTimeInterface $then): void
    {
        $thenSeconds = (float) $then->format('U.u');
        $nowSeconds = microtime(true);
        $diffMicroSeconds = ($thenSeconds - $nowSeconds) * 1_000_000;
        $this->microseconds((int) $diffMicroSeconds);
    }
}
