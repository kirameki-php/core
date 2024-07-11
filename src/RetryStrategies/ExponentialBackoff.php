<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

use Override;
use function min;
use function random_int;

class ExponentialBackoff implements RetryStrategy
{
    /**
     * @var int
     */
    protected int $previousDelay = 0;

    /**
     * @param int $baseDelayMilliseconds
     * @param int $maxDelayMilliseconds
     * @param JitterAlgorithm $jitterAlgorithm
     */
    public function __construct(
        protected int $baseDelayMilliseconds = 10,
        protected int $maxDelayMilliseconds = 1_000,
        protected JitterAlgorithm $jitterAlgorithm = JitterAlgorithm::Full,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function calculateDelayMilliSeconds(int $attempt): int
    {
        $delay = $this->baseDelayMilliseconds * (2 ** $attempt);

        $delay = match ($this->jitterAlgorithm) {
            JitterAlgorithm::None => $this->applyNoJitter($delay),
            JitterAlgorithm::Full => $this->applyFullJitter($delay),
            JitterAlgorithm::Equal => $this->applyEqualJitter($delay),
            JitterAlgorithm::Decorrelated => $this->applyDecorrelatedJitter(),
        };

        $this->previousDelay = $delay;

        return $delay;
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyNoJitter(int $delay): int
    {
        return min($delay, $this->maxDelayMilliseconds);
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyFullJitter(int $delay): int
    {
        return random_int(0, min($delay, $this->maxDelayMilliseconds));
    }

    /**
     * @param int $delay
     * @return int
     */
    protected function applyEqualJitter(int $delay): int
    {
        $temp = min($delay, $this->maxDelayMilliseconds);
        return $temp / 2 + random_int(0, $temp / 2);
    }

    protected function applyDecorrelatedJitter(): int
    {
        $delay = random_int($this->baseDelayMilliseconds, $this->previousDelay * 3);
        return min($delay, $this->maxDelayMilliseconds);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reset(): void
    {
        $this->previousDelay = 0;
    }
}

