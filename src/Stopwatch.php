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
     * @var bool
     */
    public bool $isRunning {
        get => $this->start !== null;
    }

    /**
     * @var int
     */
    public int $elapsedInNanoseconds {
        get => $this->isRunning
            ? $this->elapsed + (hrtime(true) - $this->start)
            : $this->elapsed;
    }

    /**
     * @var float
     */
    public float $elapsedInMilliseconds {
        get => $this->elapsedInNanoseconds / 1_000_000;
    }

    /**
     * @return $this
     */
    public function start(): static
    {
        if ($this->isRunning) {
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
        if (!$this->isRunning) {
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
}
