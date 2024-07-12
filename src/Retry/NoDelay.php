<?php declare(strict_types=1);

namespace Kirameki\Core\Retry;

use Override;

class NoDelay implements RetryPolicy
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function calculateDelayMilliseconds(int $attempt): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reset(): void
    {
    }
}
