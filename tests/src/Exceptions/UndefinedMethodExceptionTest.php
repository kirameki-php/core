<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use Exception as BaseException;
use JsonSerializable;
use Kirameki\Core\Exceptions\Exceptionable;
use Kirameki\Core\Exceptions\UndefinedMethodException;
use RuntimeException;
use function random_int;

final class UndefinedMethodExceptionTest extends TestCase
{
    public function test_construct(): void
    {
        $exception = new UndefinedMethodException();
        self::assertInstanceOf(BaseException::class, $exception);
        self::assertInstanceOf(Exceptionable::class, $exception);
        self::assertInstanceOf(JsonSerializable::class, $exception);
        self::assertSame([], $exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $exception = new UndefinedMethodException('t', ['a' => 1, 'b' => 2]);
        self::assertSame('t', $exception->getMessage());
        self::assertSame(['a' => 1, 'b' => 2], $exception->getContext());
    }

    public function test_construct_with_full_construct(): void
    {
        $message = 't';
        $context = [];
        $code = random_int(0, 100);
        $prev = new RuntimeException('r');
        $exception = new UndefinedMethodException($message, $context, $code, $prev);
        self::assertSame($message, $exception->getMessage());
        self::assertSame($code, $exception->getCode());
        self::assertSame($context, $exception->getContext());
        self::assertSame($prev, $exception->getPrevious());
    }

    public function test_construct_with_null_context(): void
    {
        $exception = new UndefinedMethodException('t', null);
        self::assertSame('t', $exception->getMessage());
        self::assertSame([], $exception->getContext());
    }
}
