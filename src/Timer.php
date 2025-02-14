<?php

declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\LogicException;
use function hrtime;
use function max;

class Timer
{
    /**
     * @var int
     */
    protected int $duration = 0;

    /**
     * @var int
     */
    protected int $elapsed = 0;

    /**
     * @var int|null
     */
    protected int|null $start = null;

    /**
     * @var bool
     */
    protected bool $completed = false;

    /**
     * @var bool
     */
    public bool $isRunning {
        get => $this->start !== null && !$this->isElapsed;
    }

    /**
     * @var bool
     */
    public bool $isElapsed {
        get => $this->remainingInNanoseconds === 0;
    }

    /**
     * @var int
     */
    public int $remainingInNanoseconds {
        get {
            if ($this->completed) {
                return 0;
            }

            if ($this->start === null) {
                return $this->duration - $this->elapsed;
            }

            $elapsed = hrtime(true) - ($this->elapsed + ($this->start));
            $remaining = max(0, $this->duration - $elapsed);

            if ($remaining === 0) {
                $this->completed = true;
            }

            return (int) $remaining;
        }
    }

    /**
     * @var int
     */
    public int $remainingInMilliseconds {
        get => (int) ($this->remainingInNanoseconds / 1_000_000);
    }

    /**
     * @param int $milliseconds
     */
    public function __construct(int $milliseconds)
    {
        $this->duration = $milliseconds * 1_000_000;
    }

    /**
     * @return $this
     */
    public function start(): static
    {
        if ($this->start !== null) {
            throw new LogicException('CountDownTimer is already running.');
        }

        $this->start = hrtime(true);
        return $this;
    }

    /**
     * @return $this
     */
    public function stop(): static
    {
        if ($this->start === null) {
            throw new LogicException('CountDownTimer is not running.');
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
        $this->completed = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function restart(): static
    {
        $this->reset();
        $this->start();
        return $this;
    }
}
