<?php declare(strict_types=1);

namespace Kirameki\Core\Retry;

use Override;

class ConstantDelay implements RetryPolicy
{
    /**
     * @param int $delayMilliseconds
     */
    public function __construct(
        protected int $delayMilliseconds = 100,
    )
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function calculateDelayMilliseconds(int $attempt): int
    {
        return $this->delayMilliseconds;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reset(): void
    {
    }
}
