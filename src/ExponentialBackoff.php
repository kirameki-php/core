<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Throwable;
use function is_a;
use function is_iterable;
use function is_string;
use function min;
use function random_int;

class ExponentialBackoff
{
    /**
     * @param class-string<Throwable>|iterable<class-string<Throwable>>|Closure(Throwable): bool $retryableExceptions
     * Throwable classes that should be retried.
     * @param int $baseDelayMilliseconds
     * The base delay in milliseconds to start with.
     * @param int $maxDelayMilliseconds
     * The maximum delay in milliseconds to cap at.
     * @param float $stepMultiplier
     * The multiplier to increase the delay by each attempt.
     * @param JitterAlgorithm $jitterAlgorithm
     * The jitter algorithm to use.
     * @param ?Sleep $sleep
     * The sleep instance to use. Will default to a new instance if not provided.
     */
    public function __construct(
        protected string|iterable|Closure $retryableExceptions,
        protected int $baseDelayMilliseconds = 10,
        protected int $maxDelayMilliseconds = 1_000,
        protected float $stepMultiplier = 2.0,
        protected JitterAlgorithm $jitterAlgorithm = JitterAlgorithm::Full,
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
                if ($this->shouldRetry($attempts, $maxAttempts, $e)) {
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
     * @param int $attempts
     * @param int $maxAttempts
     * @param Throwable $e
     * @return bool
     */
    protected function shouldRetry(int $attempts, int $maxAttempts, Throwable $e): bool
    {
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $exceptions = $this->retryableExceptions;

        if (is_string($exceptions)) {
            $exceptions = [$exceptions];
        }

        if (is_iterable($exceptions)) {
            foreach ($exceptions as $exception) {
                if (is_a($e, $exception, true)) {
                    return true;
                }
            }
            return false;
        }

        return $exceptions($e);
    }

    /**
     * @param int $milliseconds
     */
    protected function sleep(int $milliseconds): void
    {
        $this->sleep ??= new Sleep();
        $this->sleep->milliseconds($milliseconds);
    }

    /**
     * @param int $attempt
     * @param int $previousDelay
     * @return int
     */
    public function calculateDelay(int $attempt, int $previousDelay): int
    {
        $delay = $this->baseDelayMilliseconds * ($this->stepMultiplier ** $attempt);

        $delay = match ($this->jitterAlgorithm) {
            JitterAlgorithm::None => $this->applyNoJitter($delay),
            JitterAlgorithm::Full => $this->applyFullJitter($delay),
            JitterAlgorithm::Equal => $this->applyEqualJitter($delay),
            JitterAlgorithm::Decorrelated => $this->applyDecorrelatedJitter($previousDelay),
        };

        return $delay;
    }

    /**
     * @param float $delay
     * @return int
     */
    protected function applyNoJitter(float $delay): int
    {
        return (int) min($delay, $this->maxDelayMilliseconds);
    }

    /**
     * @param float $delay
     * @return int
     */
    protected function applyFullJitter(float $delay): int
    {
        return random_int(0, (int) min($delay, $this->maxDelayMilliseconds));
    }

    /**
     * @param float $delay
     * @return int
     */
    protected function applyEqualJitter(float $delay): int
    {
        $temp = (int) min($delay, $this->maxDelayMilliseconds);
        return $temp / 2 + random_int(0, $temp / 2);
    }

    /**
     * @param int $previousDelay
     * @return int
     */
    protected function applyDecorrelatedJitter(int $previousDelay): int
    {
        $delay = random_int($this->baseDelayMilliseconds, $previousDelay * 3);
        return min($delay, $this->maxDelayMilliseconds);
    }
}
