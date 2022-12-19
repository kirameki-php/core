<?php declare(strict_types=1);

namespace Tests\SouthPointe\Core\Exception;

use SouthPointe\Core\Exception\Exception;

class HandlesContextTest extends TestCase
{
    public function test_addContext(): void
    {
        $exception = new Exception();
        $exception->addContext('a', 1);
        $exception->addContext('b', 2); // append
        $exception->addContext('a', 3); // override
        self::assertEquals(['a' => 3, 'b' => 2], $exception->getContext());
    }

    public function test_mergeContext(): void
    {
        $exception = new Exception();
        $exception->mergeContext(['a' => 1]);
        self::assertEquals(['a' => 1], $exception->getContext());

        $exception->mergeContext(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 2, 'b' => 2], $exception->getContext());
    }

    public function test_setContext(): void
    {
        $exception = new Exception();
        $exception->setContext(['a' => 1]);
        $exception->setContext(['b' => 2]);
        self::assertEquals(['b' => 2], $exception->getContext());
    }
}
