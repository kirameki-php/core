<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Random\Randomizer;
use Throwable;
use function is_a;
use function is_iterable;
use function is_string;
use function min;

class ExponentialBackoff
{
    /**
     * @param class-string<Throwable>|iterable<class-string<Throwable>>|Closure(Throwable): bool $catchExceptions
     * Throwable classes that should be retried.
     * @param int $baseDelayMilliseconds
     * The base delay in milliseconds to start with.
     * @param int $maxDelayMilliseconds
     * The maximum delay in milliseconds to cap at.
     * @param float $stepMultiplier
     * The multiplier to increase the delay by each attempt.
     * @param JitterStrategy $jitterStrategy
     * The jitter algorithm to use.
     * @param Randomizer|null $randomizer
     * The randomizer instance to use. Will default to a new instance if not provided.
     * @param Sleep|null $sleep
     * The sleep instance to use. Will default to a new instance if not provided.
     */
    public function __construct(
        protected string|iterable|Closure $catchExceptions,
        protected int $baseDelayMilliseconds = 10,
        protected int $maxDelayMilliseconds = 1_000,
        protected float $stepMultiplier = 2.0,
        protected JitterStrategy $jitterStrategy = JitterStrategy::Full,
        protected ?Randomizer $randomizer = null,
        protected ?Sleep $sleep = null,
    )
    {
    }

    /**
     * @template TResult
     * @param Closure(int): TResult $call
     * @return TResult
     */
    public function run(int $maxAttempts, Closure $call): mixed
    {
        $previousDelay = 0;
        $attempts = 1;
        while(true) {
            try {
                return $call($attempts);
            } catch (Throwable $e) {
                if ($this->canRetry($attempts, $maxAttempts, $e)) {
                    $delay = $this->calculateDelay($attempts, $previousDelay);
                    $this->sleep($delay);
                    $attempts++;
                    $previousDelay = $delay;
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param int $milliseconds
     * @return void
     */
    protected function sleep(int $milliseconds): void
    {
        $this->sleep ??= new Sleep();
        $this->sleep->milliseconds($milliseconds);
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function random(int $min, int $max): int
    {
        $this->randomizer ??= new Randomizer();
        return $this->randomizer->getInt($min, $max);
    }

    /**
     * @param int $attempts
     * @param int $maxAttempts
     * @param Throwable $exception
     * @return bool
     */
    protected function canRetry(int $attempts, int $maxAttempts, Throwable $exception): bool
    {
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $retryables = $this->catchExceptions;

        if (is_string($retryables)) {
            $retryables = [$retryables];
        }

        if (is_iterable($retryables)) {
            foreach ($retryables as $retryable) {
                if (is_a($exception, $retryable, true)) {
                    return true;
                }
            }
            return false;
        }

        return $retryables($exception);
    }

    /**
     * @param int $attempt
     * @param int $previousDelay
     * @return int
     */
    public function calculateDelay(int $attempt, int $previousDelay): int
    {
        $delay = $this->baseDelayMilliseconds * ($this->stepMultiplier ** $attempt);
        return $this->addJitter((int) $delay, $previousDelay);
    }

    protected function addJitter(int $delay, int $previousDelay): int
    {
        return match ($this->jitterStrategy) {
            JitterStrategy::None => $this->applyNoJitter($delay),
            JitterStrategy::Full => $this->applyFullJitter($delay),
            JitterStrategy::Equal => $this->applyEqualJitter($delay),
            JitterStrategy::Decorrelated => $this->applyDecorrelatedJitter($previousDelay),
        };
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyNoJitter(int $delay): int
    {
        return $this->clampDelay($delay);
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyFullJitter(int $delay): int
    {
        return $this->random(0, $this->clampDelay($delay));
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyEqualJitter(int $delay): int
    {
        $temp = $this->clampDelay($delay);
        return $temp / 2 + $this->random(0, $temp / 2);
    }

    /**
     * @param int $previousDelay
     * @return int
     */
    protected function applyDecorrelatedJitter(int $previousDelay): int
    {
        $delay = $this->random($this->baseDelayMilliseconds, $previousDelay * 3);
        return $this->clampDelay($delay);
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function clampDelay(int $delay): int
    {
        return min($delay, $this->maxDelayMilliseconds);
    }
}
