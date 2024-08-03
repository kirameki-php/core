<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTimeImmutable;
use Kirameki\Core\Sleep;
use Kirameki\Core\Testing\SleepMock;
use Kirameki\Core\Testing\TestCase;
use function hrtime;

final class SleepTest extends TestCase
{
    protected function getTotalSleptMicroseconds(Sleep $sleep): int
    {
        return array_sum($sleep->getHistory());
    }

    public function test_non_mock_actually_sleeps(): void
    {
        $sleep = new Sleep();
        $start = hrtime(true);
        $sleep->microseconds(1);
        $end = hrtime(true);
        $diff = $end - $start;
        $this->assertGreaterThanOrEqual(1_000, $diff);
        $this->assertLessThan(1e7, $diff); // 10ms
    }

    public function test_seconds(): void
    {
        $sleep = new SleepMock();
        $sleep->seconds(1);
        $this->assertSame(1_000_000, $this->getTotalSleptMicroseconds($sleep));
    }

    public function test_milliseconds(): void
    {
        $sleep = new SleepMock();
        $sleep->milliseconds(1);
        $this->assertSame(1_000, $this->getTotalSleptMicroseconds($sleep));
    }

    public function test_microseconds(): void
    {
        $sleep = new SleepMock();
        $sleep->microseconds(1);
        $this->assertSame(1, $this->getTotalSleptMicroseconds($sleep));
    }

    public function test_until(): void
    {
        $sleep = new SleepMock();
        $sleep->until(new DateTimeImmutable("+1 second"));
        $this->assertLessThanOrEqual(1_000_000, $this->getTotalSleptMicroseconds($sleep));
    }

    public function test_getHistory(): void
    {
        $sleep = new SleepMock();
        $sleep->seconds(1);
        $sleep->milliseconds(1);
        $sleep->microseconds(1);
        $this->assertSame([1_000_000, 1_000, 1], $sleep->getHistory());
    }

    public function test_clearHistory(): void
    {
        $sleep = new SleepMock();
        $sleep->seconds(1);
        $sleep->clearHistory();
        $this->assertSame([], $sleep->getHistory());
    }
}
