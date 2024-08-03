<?php declare(strict_types=1);

namespace Kirameki\Core\Testing;

use Closure;
use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Core\Exceptions\LogicException;
use PHPUnit\Framework\TestCase as BaseTestCase;
use function array_map;
use function restore_error_handler;
use function set_error_handler;
use const E_ALL;
use const E_COMPILE_WARNING;
use const E_CORE_WARNING;
use const E_USER_WARNING;
use const E_WARNING;

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
        $called = false;
        set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$called) {
            restore_error_handler();
            $called = true;
            throw new ErrorException($message, $severity, $file, $line);
        }, $level);

        $this->runBeforeTearDown(static function () use (&$called) {
            if (!$called) {
                restore_error_handler();
            }
        });
    }

    public function expectErrorMessage(string $message, int $level = E_ALL): void
    {
        $this->throwOnError($level);
        $this->expectExceptionMessage($message);
        $this->expectException(ErrorException::class);
    }

    public function expectWarningMessage(string $message): void
    {
        $this->expectErrorMessage($message, E_WARNING | E_CORE_WARNING | E_USER_WARNING | E_COMPILE_WARNING);
    }
}
