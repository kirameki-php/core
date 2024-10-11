<?php

declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\LogicException;
use function hrtime;

class Stopwatch
{
    /**
     * @var int
     */
    protected int $elapsed = 0;

    /**
     * @var int|null
     */
    protected ?int $start = null;

    /**
     * @return $this
     */
    public function start(): static
    {
        if ($this->isRunning()) {
            throw new LogicException('Stopwatch is already running.');
        }

        $this->start = hrtime(true);
        return $this;
    }

    /**
     * @return $this
     */
    public function stop(): static
    {
        if (!$this->isRunning()) {
            throw new LogicException('Stopwatch is not running.');
        }

        $this->elapsed += hrtime(true) - $this->start;
        $this->start = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->elapsed = 0;
        $this->start = null;
        return $this;
    }

    /**
     * @return void
     */
    public function restart(): void
    {
        $this->reset();
        $this->start();
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->start !== null;
    }

    /**
     * @return int
     */
    public function getElapsedNanoSeconds(): int
    {
        return $this->isRunning()
            ? $this->elapsed + (hrtime(true) - $this->start)
            : $this->elapsed;
    }

    /**
     * @return float
     */
    public function getElapsedMilliSeconds(): float
    {
        return $this->getElapsedNanoSeconds() / 1_000_000;
    }
}
