<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Stopwatch;
use Kirameki\Core\Testing\TestCase;
use function usleep;

final class StopwatchTest extends TestCase
{
    public function test_start(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->stop()->start();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->reset()->start();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
    }

    public function test_start_when_running(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start();
        $this->expectExceptionMessage('Stopwatch is already running.');
        $stopwatch->start();
    }

    public function test_stop(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start()->stop();
        $this->assertGreaterThan(0, $prev = $stopwatch->getElapsedNanoSeconds());
        usleep(1000);
        $this->assertSame($prev, $stopwatch->getElapsedNanoSeconds());
    }

    public function test_stop_when_not_running(): void
    {
        $stopwatch = new Stopwatch();
        $this->expectExceptionMessage('Stopwatch is not running.');
        $stopwatch->stop();
    }

    public function test_reset(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->reset();
        $this->assertSame(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->start()->reset();
        $this->assertSame(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->start()->stop()->reset();
        $this->assertSame(0, $stopwatch->getElapsedNanoSeconds());
    }

    public function test_restart(): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->restart();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->stop()->restart();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->reset()->restart();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
    }

    public function test_isRunning(): void
    {
        $stopwatch = new Stopwatch();
        $this->assertFalse($stopwatch->isRunning());
        $stopwatch->start();
        $this->assertTrue($stopwatch->isRunning());
        $stopwatch->stop();
        $this->assertFalse($stopwatch->isRunning());
        $stopwatch->reset();
        $this->assertFalse($stopwatch->isRunning());
        $stopwatch->restart();
        $this->assertTrue($stopwatch->isRunning());
    }

    public function test_getElapsedNanoSeconds(): void
    {
        $stopwatch = new Stopwatch();
        $this->assertSame(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->start();
        $this->assertGreaterThan(0, $prev = $stopwatch->getElapsedNanoSeconds());
        $stopwatch->stop();
        $this->assertGreaterThan($prev, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->reset();
        $this->assertSame(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->start();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
        $stopwatch->restart();
        $this->assertGreaterThan(0, $stopwatch->getElapsedNanoSeconds());
    }

    public function test_getElapsedMilliSeconds(): void
    {
        $stopwatch = new Stopwatch();
        $this->assertSame(0.0, $stopwatch->getElapsedMilliSeconds());
        $stopwatch->start();
        usleep(1000);
        $stopwatch->stop();
        $this->assertGreaterThan(1, $stopwatch->getElapsedMilliSeconds());
        $stopwatch->start();
        usleep(1000);
        $stopwatch->stop();
        $this->assertGreaterThan(2, $stopwatch->getElapsedMilliSeconds());
    }
}
