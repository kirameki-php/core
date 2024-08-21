<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use BadMethodCallException;
use Kirameki\Core\Exceptions\Exception;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Core\ExponentialBackoff;
use Kirameki\Core\JitterStrategy;
use Kirameki\Core\Testing\SleepMock;
use Kirameki\Core\Testing\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use function array_splice;
use function array_sum;
use function random_int;

final class ExponentialBackoffTest extends TestCase
{
    public function test_without_backoff(): void
    {
        $backoff = new ExponentialBackoff(RuntimeException::class);
        $this->assertSame('t', $backoff->run(1, fn() => 't'));
    }

    public function test_run_zero(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Max attempts must be at least 1.');

        $backoff = new ExponentialBackoff(RuntimeException::class);
        $backoff->run(0, fn() => 1);
    }

    public function test_catch_closure(): void
    {
        $count = 0;
        $backoff = new ExponentialBackoff(function($e) use (&$count) {
            $count++;
            return match ($count) {
                1 => $e instanceof RuntimeException,
                2 => $e instanceof LogicException,
                default => throw new Exception('x'),
            };
        }, sleep: new SleepMock());

        $times = 0;
        $result = $backoff->run(3, function() use (&$times) {
            $times++;
            if ($times === 1) {
                throw new RuntimeException('1');
            }
            if ($times === 2) {
                throw new LogicException('2');
            }
            return 't';
        });
        $this->assertSame(3, $times);
        $this->assertSame('t', $result);
    }

    public function test_catch_instanceOf(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('count: 2');

        $backoff = new ExponentialBackoff(LogicException::class, sleep: new SleepMock());
        $backoff->run(2, function() use (&$count) {
            throw new InvalidArgumentException('count: ' . ++$count);
        });
    }

    public function test_catch_instanceOf_from_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('count: 2');

        $backoff = new ExponentialBackoff([LogicException::class, RuntimeException::class], sleep: new SleepMock());
        $count = 0;
        $backoff->run(2, function() use (&$count) {
            throw new InvalidArgumentException('count: ' . ++$count);
        });
    }

    public function test_catch_array_both(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('count: 3');

        $backoff = new ExponentialBackoff([LogicException::class, RuntimeException::class], sleep: new SleepMock());
        $count = 0;
        $backoff->run(3, function() use (&$count) {
            ++$count;
            if ($count % 2 === 0) {
                throw new LogicException('count: ' . $count);
            } else {
                throw new RuntimeException('count: ' . $count);
            }
        });
    }

    public function test_non_catchable_error(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('test');

        $backoff = new ExponentialBackoff(RuntimeException::class, sleep: new SleepMock());
        $backoff->run(1, function() {
            throw new LogicException('test');
        });
    }

    public function test_non_catchable_errors(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('test');

        $backoff = new ExponentialBackoff([RuntimeException::class, BadMethodCallException::class], sleep: new SleepMock());
        $backoff->run(2, function() {
            throw new LogicException('test');
        });
    }

    public function test_run_once_success(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(RuntimeException::class, sleep: $sleep);
        $this->assertSame('t', $backoff->run(1, fn() => 't'));
        $this->assertSame(0, array_sum($sleep->getHistory()));
    }

    public function test_run_once_failure(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(RuntimeException::class, sleep: $sleep);
        $count = 0;
        $caught = false;
        try {
            $backoff->run(1, function() use (&$count) {
                throw new RuntimeException('times: ' . (++$count));
            });
        } catch (RuntimeException $e) {
            $this->assertSame('times: 1', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
        $this->assertSame(0, array_sum($sleep->getHistory()));
    }

    public function test_run_multi_success(): void
    {
        $sleep = new SleepMock();
        $jitter = JitterStrategy::None;
        $backoff = new ExponentialBackoff(RuntimeException::class, jitterStrategy: $jitter, sleep: $sleep);
        $count = 0;
        $times = 5;
        $this->assertSame('t', $backoff->run($times, function() use ($times, &$count) {
            if (++$count < $times) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        }));
        $this->assertSame([5_000, 10_000, 20_000, 40_000], $sleep->getHistory());
    }

    public function test_run_multi_failure(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(RuntimeException::class, sleep: $sleep);
        $count = 0;
        $caught = false;
        try {
            $backoff->run(4, function() use (&$count) {
                $count++;
                throw new RuntimeException('times: ' . $count);
            });
        } catch (RuntimeException $e) {
            $this->assertSame('times: 4', $e->getMessage());
            $caught = true;
        }
        $this->assertTrue($caught);
        $this->assertCount(3, $sleep->getHistory());
    }

    public function test_change_defaultDelay(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            baseDelayMilliseconds: 1,
            jitterStrategy: JitterStrategy::None,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(4, function() use (&$count) {
            $count++;
            if ($count < 4) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([1_000, 2_000, 4_000], $sleep->getHistory());
    }

    public function test_change_max_delay(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            baseDelayMilliseconds: 4,
            maxDelayMilliseconds: 10,
            jitterStrategy: JitterStrategy::None,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(5, function() use (&$count) {
            $count++;
            if ($count < 5) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([4_000, 8_000, 10_000, 10_000], $sleep->getHistory());
    }

    public function test_change_step_multiplier(): void
    {
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            baseDelayMilliseconds: 1,
            stepMultiplier: 1,
            jitterStrategy: JitterStrategy::None,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(4, function() use (&$count) {
            $count++;
            if ($count < 4) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([1_000, 1_000, 1_000], $sleep->getHistory());
    }

    public function test_jitter_strategy_full(): void
    {
        $seed = 0;
        $randomizer = new Randomizer(new Xoshiro256StarStar($seed));
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            jitterStrategy: JitterStrategy::Full,
            randomizer: $randomizer,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(4, function() use (&$count) {
            $count++;
            if ($count < 4) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([4_000, 2_000, 17_000], $sleep->getHistory());
    }

    public function test_jitter_strategy_equal(): void
    {
        $seed = 4;
        $randomizer = new Randomizer(new Xoshiro256StarStar($seed));
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            jitterStrategy: JitterStrategy::Equal,
            randomizer: $randomizer,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(4, function() use (&$count) {
            $count++;
            if ($count < 4) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([3_000, 7_000, 19_000], $sleep->getHistory());
    }

    public function test_jitter_strategy_decorrelated(): void
    {
        $seed = 0;
        $randomizer = new Randomizer(new Xoshiro256StarStar($seed));
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            jitterStrategy: JitterStrategy::Decorrelated,
            randomizer: $randomizer,
            sleep: $sleep,
        );
        $count = 0;
        $backoff->run(4, function() use (&$count) {
            $count++;
            if ($count < 4) {
                throw new RuntimeException('times: ' . $count);
            }
            return 't';
        });
        $this->assertSame([4_000, 7_000, 5_000], $sleep->getHistory());
    }

    public function test_different_randomizer(): void
    {
        $randomizer = new Randomizer(new Xoshiro256StarStar(0));
        $sleep = new SleepMock();
        $backoff = new ExponentialBackoff(
            RuntimeException::class,
            jitterStrategy: JitterStrategy::None,
            randomizer: $randomizer,
            sleep: $sleep,
        );
        for ($i = 0; $i < 4; $i++) {
            $count = 0;
            $backoff->run(4, function() use (&$count) {
                $count++;
                if ($count < 4) {
                    throw new RuntimeException('times: ' . $count);
                }
                return 't';
            });
            $history = $sleep->getHistory();
            $this->assertSame([5_000, 1_0000, 2_0000], array_splice($history, -3, 3));
            $this->assertCount(3 * $i, $history);
        }
    }
}
