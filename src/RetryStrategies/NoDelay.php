<?php declare(strict_types=1);

namespace Kirameki\Core\RetryStrategies;

use Override;

class NoDelay implements RetryStrategy
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function calculateDelayMilliSeconds(int $attempt): int
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

