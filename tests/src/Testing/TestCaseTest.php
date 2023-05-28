<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Testing;

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
}
