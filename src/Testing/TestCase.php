<?php declare(strict_types=1);

namespace Kirameki\Core\Testing;

use Closure;
use Kirameki\Core\Exceptions\ErrorException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use function var_dump;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var array<Closure(): mixed>
     */
    private array $beforeSetupCallbacks = [];

    /**
     * @var array<Closure(): mixed>
     */
    private array $afterSetupCallbacks = [];

    /**
     * @var array<Closure(): mixed>
     */
    private array $beforeTearDownCallbacks = [];

    /**
     * @var array<Closure(): mixed>
     */
    private array $afterTearDownCallbacks = [];

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    protected function runBeforeSetup(Closure $callback): void
    {
        $this->beforeSetupCallbacks[] = $callback;
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    protected function runAfterSetup(Closure $callback): void
    {
        $this->afterSetupCallbacks[] = $callback;
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    protected function runBeforeTearDown(Closure $callback): void
    {
        $this->beforeTearDownCallbacks[]= $callback;
    }

    /**
     * @param Closure(): mixed $callback
     * @return void
     */
    protected function runAfterTearDown(Closure $callback): void
    {
        $this->afterTearDownCallbacks[]= $callback;
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        array_map(static fn(Closure $callback) => $callback(), $this->beforeSetupCallbacks);
        parent::setUp();
        array_map(static fn(Closure $callback) => $callback(), $this->afterSetupCallbacks);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        array_map(static fn(Closure $callback) => $callback(), $this->beforeTearDownCallbacks);
        parent::tearDown();
        array_map(static fn(Closure $callback) => $callback(), $this->afterTearDownCallbacks);
    }

    /**
     * @param int $level
     * @return void
     */
    protected function throwOnError(int $level = E_ALL): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line) {
            restore_error_handler();
            throw new ErrorException($message, $severity, $file, $line);
        }, $level);
    }
}
