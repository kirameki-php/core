<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Testing\TestCase;

final class SleepTest extends TestCase
{
    public function test_sleep(): void
    {
        $start = microtime(true);
        sleep(1);
        $end = microtime(true);
        $elapsed = $end - $start;
        self::assertGreaterThanOrEqual(1, $elapsed);
    }
}
