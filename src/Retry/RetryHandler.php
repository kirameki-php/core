<?php declare(strict_types=1);

namespace Kirameki\Core\Retry;

use Closure;
use Throwable;
use function is_a;
use function usleep;

class RetryHandler
{
    /**
     * @var RetryPolicy
     */
    protected RetryPolicy $strategy;

    /**
     * @param class-string<Throwable>|iterable<class-string<Throwable>>|Closure(Throwable): bool $retryableThrowables
     * Throwable classes that should be retried.
     * @param int $maxAttempts
     * Maximum number of attempts
     * @param RetryPolicy $strategy
     * Strategy to use for calculating delays.
     * Default: ExponentialBackoff
     */
    public function __construct(
        protected string|iterable|Closure $retryableThrowables,
        protected int $maxAttempts,
        ?RetryPolicy $strategy = null,
    )
    {
        $this->strategy = $strategy ?? new ExponentialBackoff();
    }

    /**
     * @template TResult
     * @param Closure(): TResult $call
     * @return TResult
     */
    public function run(Closure $call): mixed
    {
        $strategy = $this->strategy;
        $attempts = 0;
        while(true) {
            try {
                $attempts++;
                $result = $call();
                $strategy->reset();
                return $result;
            } catch (Throwable $e) {
                if ($this->shouldRetry($attempts, $e)) {
                    $delay = $strategy->calculateDelayMilliseconds($attempts);
                    $this->sleep($delay);
                    continue;
                }
                $strategy->reset();
                throw $e;
            }
        }
    }

    /**
     * @param int $attempts
     * @param Throwable $e
     * @return bool
     */
    protected function shouldRetry(int $attempts, Throwable $e): bool
    {
        if ($attempts >= $this->maxAttempts) {
            return false;
        }

        $throwables = $this->retryableThrowables;

        if (is_string($throwables)) {
            $throwables = [$throwables];
        }

        if (is_iterable($throwables)) {
            foreach ($throwables as $throwable) {
                if (is_a($e, $throwable, true)) {
                    return true;
                }
            }
            return false;
        }

        return $throwables($e);
    }

    /**
     * @param int $milliseconds
     */
    protected function sleep(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}
