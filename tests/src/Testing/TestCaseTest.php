<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Testing;

use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Core\Testing\TestCase;

final class TestCaseTest extends TestCase
{
    protected function setUp(): void
    {
        $this->runBeforeSetup(function() {
            $this->runBeforeTearDown(fn() => $this->assertTrue(true));
        });
        $this->runAfterSetup(function() {
            $this->runBeforeTearDown(fn() => $this->assertTrue(true));
        });
        parent::setUp();
    }

    public function test_runBeforeTearDown(): void
    {
        $this->runBeforeTearDown(fn() => $this->assertTrue(true));
    }

    public function test_runAfterTearDown(): void
    {
        $this->runAfterTearDown(fn() => $this->assertTrue(true));
    }

    public function test_throwOnError(): void
    {
        $this->throwOnError();
        $this->expectExceptionMessage('Undefined array key 1');
        $this->expectException(ErrorException::class);
        $arr = [];
        $arr[1];
    }

    public function test_throwOnError_different_level(): void
    {
        $this->throwOnError(E_WARNING);
        $this->expectExceptionMessage('Undefined array key 1');
        $this->expectException(ErrorException::class);
        $arr = [];
        $arr[1];
    }
}
