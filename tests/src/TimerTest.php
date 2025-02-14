<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Testing\TestCase;
use Kirameki\Core\Timer;
use function usleep;

final class TimerTest extends TestCase
{
    public function test_start(): void
    {
        $timer = new Timer(100);
        $this->assertSame(100, $timer->remainingInMilliseconds);
        $timer->start();
        $this->assertGreaterThan(0, $timer->remainingInMilliseconds);
        $this->assertLessThan(100, $timer->remainingInMilliseconds);
    }

    public function test_start_when_running(): void
    {
        $timer = new Timer(100);
        $timer->start();
        $this->expectExceptionMessage('Timer is already running.');
        $timer->start();
    }

    public function test_stop(): void
    {
        $timer = new Timer(100);
        $timer->start();
        $this->assertGreaterThan(0, $prev = $timer->remainingInMilliseconds);
        $this->assertLessThan(100, $prev);
        usleep(1000);
        $this->assertLessThan($prev, $timer->remainingInMilliseconds);
    }

    public function test_stop_when_not_running(): void
    {
        $timer = new Timer(100);
        $this->expectExceptionMessage('Timer is not running.');
        $timer->stop();
    }

    public function test_reset(): void
    {
        $timer = new Timer(100);
        $timer->reset();
        $this->assertSame(100, $timer->remainingInMilliseconds);
        $timer->start()->reset();
        $this->assertSame(100, $timer->remainingInMilliseconds);
        $timer->start()->stop()->reset();
        $this->assertSame(100, $timer->remainingInMilliseconds);
    }

    public function test_restart(): void
    {
        $timer = new Timer(100);
        $timer->start();
        $this->assertGreaterThan(0, $timer->remainingInMilliseconds);
        $timer->restart();
        $this->assertGreaterThan(0, $timer->remainingInMilliseconds);
    }

    public function test_isElapsed(): void
    {
        $timer = new Timer(100);
        $this->assertFalse($timer->isElapsed);
        $timer->start();
        $this->assertFalse($timer->isElapsed);
        usleep(100_000);
        $this->assertTrue($timer->isElapsed);
    }

    public function test_isRunning(): void
    {
        $timer = new Timer(100);
        $this->assertFalse($timer->isRunning);
        $timer->start();
        $this->assertTrue($timer->isRunning);
        $timer->stop();
        $this->assertFalse($timer->isRunning);
        $timer->reset();
        $this->assertFalse($timer->isRunning);
        $timer->restart();
        $this->assertTrue($timer->isRunning);
    }

    public function test_getRemainingNanoseconds(): void
    {
        $timer = new Timer(100);
        $this->assertSame(100_000_000, $timer->remainingInNanoseconds);
        $timer->start();
        $this->assertGreaterThan(0, $timer->remainingInNanoseconds);
        $this->assertLessThan(100_000_000, $timer->remainingInNanoseconds);
        usleep(100_000);
        $this->assertLessThan(100_000_000, $timer->remainingInNanoseconds);
        $this->assertLessThan(100_000_000, $timer->remainingInNanoseconds);
    }

    public function test_getRemainingMilliseconds(): void
    {
        $timer = new Timer(100);
        $this->assertSame(100, $timer->remainingInMilliseconds);
        $timer->start();
        $this->assertGreaterThan(0, $timer->remainingInMilliseconds);
        $this->assertLessThan(100, $timer->remainingInMilliseconds);
    }
}
