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
        $this->assertInstanceOf(BaseException::class, $exception);
        $this->assertInstanceOf(Exceptionable::class, $exception);
        $this->assertInstanceOf(JsonSerializable::class, $exception);
        $this->assertSame([], $exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $exception = new UndefinedMethodException('t', ['a' => 1, 'b' => 2]);
        $this->assertSame('t', $exception->getMessage());
        $this->assertSame(['a' => 1, 'b' => 2], $exception->getContext());
    }

    public function test_construct_with_full_construct(): void
    {
        $message = 't';
        $context = [];
        $code = random_int(0, 100);
        $prev = new RuntimeException('r');
        $exception = new UndefinedMethodException($message, $context, $code, $prev);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($context, $exception->getContext());
        $this->assertSame($prev, $exception->getPrevious());
    }

    public function test_construct_with_null_context(): void
    {
        $exception = new UndefinedMethodException('t', null);
        $this->assertSame('t', $exception->getMessage());
        $this->assertSame([], $exception->getContext());
    }
}
