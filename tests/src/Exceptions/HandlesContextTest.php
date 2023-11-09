<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use Kirameki\Core\Exceptions\Exception;

final class HandlesContextTest extends TestCase
{
    public function test_addContext(): void
    {
        $exception = new Exception();
        $exception->addContext('a', 1);
        $exception->addContext('b', 2); // append
        $exception->addContext('a', 3); // override
        $this->assertSame(['a' => 3, 'b' => 2], $exception->getContext());
    }

    public function test_mergeContext(): void
    {
        $exception = new Exception();
        $exception->mergeContext(['a' => 1]);
        $this->assertSame(['a' => 1], $exception->getContext());

        $exception->mergeContext(['b' => 2, 'a' => 2]);
        $this->assertSame(['a' => 2, 'b' => 2], $exception->getContext());
    }

    public function test_setContext(): void
    {
        $exception = new Exception();
        $exception->setContext(['a' => 1]);
        $exception->setContext(['b' => 2]);
        $this->assertSame(['b' => 2], $exception->getContext());
    }
}
