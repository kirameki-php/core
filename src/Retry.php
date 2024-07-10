<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;
use Kirameki\Core\RetryStrategies\ExponentialBackoff;
use Kirameki\Core\RetryStrategies\RetryStrategy;
use Throwable;
use function is_a;
use function usleep;

class Retry
{
    /**
     * @var RetryStrategy
     */
    protected RetryStrategy $strategy;

    /**
     * @param class-string<Throwable>|iterable<array-key, class-string<Throwable>>|Closure(Throwable): bool $retryableThrowables
     * @param RetryStrategy $strategy
     */
    public function __construct(
        protected string|iterable|Closure $retryableThrowables,
        ?RetryStrategy $strategy = null,
    )
    {
        $this->strategy = $strategy ?? new ExponentialBackoff();
    }

    /**
     * @template TResult
     * @param Closure(): TResult $call
     * @param int $maxAttempts
     * @return TResult
     */
    public function run(Closure $call, int $maxAttempts): mixed
    {
        $attempts = 0;
        start:
        try {
            $attempts++;
            return $call();
        } catch (Throwable $e) {
            if ($attempts < $maxAttempts && $this->shouldRetry($e)) {
                $delay = $this->strategy->calculateDelayMicroSeconds($attempts);
                if ($delay > 0) {
                    usleep($delay);
                }
                goto start;
            }
            throw $e;
        }
    }

    /**
     * @param Throwable $e
     * @return bool
     */
    protected function shouldRetry(Throwable $e): bool
    {
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
}
