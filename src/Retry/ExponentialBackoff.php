<?php declare(strict_types=1);

namespace Kirameki\Core\Retry;

use Override;
use function min;
use function random_int;

class ExponentialBackoff implements RetryPolicy
{
    /**
     * @var int
     */
    protected int $previousDelay = 0;

    /**
     * @param int $baseDelayMilliseconds
     * @param int $maxDelayMilliseconds
     * @param float $backoffMultiplier
     * @param JitterAlgorithm $jitterAlgorithm
     */
    public function __construct(
        protected int $baseDelayMilliseconds = 10,
        protected int $maxDelayMilliseconds = 1_000,
        protected float $backoffMultiplier = 2.0,
        protected JitterAlgorithm $jitterAlgorithm = JitterAlgorithm::Full,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function calculateDelayMilliseconds(int $attempt): int
    {
        $delay = $this->baseDelayMilliseconds * ($this->backoffMultiplier ** $attempt);

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
