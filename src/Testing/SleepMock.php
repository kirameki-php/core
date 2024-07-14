<?php declare(strict_types=1);

namespace Kirameki\Core\Testing;

use Kirameki\Core\Sleep;
use Override;
use function array_sum;
use function assert;

class SleepMock extends Sleep
{
    /**
     * Keeps track of all sleep durations in microseconds.
     *
     * @var list<int>
     */
    protected array $sleepHistory = [];

    /**
     * @inheritDoc
     */
    #[Override]
    public function microseconds(int $duration): void
    {
        $this->sleepHistory[] = $duration;
    }

    /**
     * @return list<int>
     */
    public function getSleepHistory(): array
    {
        return $this->sleepHistory;
    }

    public function assertSleptForSeconds(int $expected, ?string $description = null): void
    {
        $this->assertSleptForMicroseconds($expected * 1_000_000, $description);
    }

    public function assertSleptForMilliseconds(int $expected, ?string $description = null): void
    {
        $this->assertSleptForMicroseconds($expected * 1_000, $description);
    }

    public function assertSleptForMicroseconds(int $expected, ?string $description = null): void
    {
        assert(array_sum($this->sleepHistory) === $expected, $description);
    }
}
