<?php declare(strict_types=1);

namespace Tests\SouthPointe\Core\Exceptions;

use Exception as BaseException;
use JsonSerializable;
use RuntimeException;
use SouthPointe\Core\Exceptions\ContextualThrowable;
use SouthPointe\Core\Exceptions\LogicException;
use function random_int;

class LogicExceptionTest extends TestCase
{
    public function test_construct(): void
    {
        $exception = new LogicException();
        self::assertInstanceOf(BaseException::class, $exception);
        self::assertInstanceOf(ContextualThrowable::class, $exception);
        self::assertInstanceOf(JsonSerializable::class, $exception);
        self::assertNull($exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $exception = new LogicException('t', ['a' => 1, 'b' => 2]);
        self::assertEquals('t', $exception->getMessage());
        self::assertEquals(['a' => 1, 'b' => 2], $exception->getContext());
    }

    public function test_construct_with_full_construct(): void
    {
        $message = 't';
        $context = [];
        $code = random_int(0, 100);
        $prev = new RuntimeException('r');
        $exception = new LogicException($message, $context, $code, $prev);
        self::assertEquals($message, $exception->getMessage());
        self::assertEquals($code, $exception->getCode());
        self::assertEquals($context, $exception->getContext());
        self::assertEquals($prev, $exception->getPrevious());
    }

    public function test_construct_with_null_context(): void
    {
        $exception = new LogicException('t', null);
        self::assertEquals('t', $exception->getMessage());
        self::assertEquals(null, $exception->getContext());
    }
}
