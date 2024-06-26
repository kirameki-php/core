<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use JsonSerializable;
use Kirameki\Core\Exceptions\Exceptionable;
use Kirameki\Core\Exceptions\ExtensionRequiredException;
use Kirameki\Core\Exceptions\LogicException;
use Kirameki\Core\Exceptions\RuntimeException;
use function random_int;

final class ExtensionRequiredExceptionTest extends TestCase
{
    public function test_construct(): void
    {
        $exception = new ExtensionRequiredException();
        $this->assertInstanceOf(LogicException::class, $exception);
        $this->assertInstanceOf(Exceptionable::class, $exception);
        $this->assertInstanceOf(JsonSerializable::class, $exception);
        $this->assertSame([], $exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $exception = new ExtensionRequiredException('t', ['a' => 1, 'b' => 2]);
        $this->assertSame('t', $exception->getMessage());
        $this->assertSame(['a' => 1, 'b' => 2], $exception->getContext());
    }

    public function test_construct_with_full_construct(): void
    {
        $message = 't';
        $context = [];
        $code = random_int(0, 100);
        $prev = new RuntimeException('r');
        $exception = new ExtensionRequiredException($message, $context, $code, $prev);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($context, $exception->getContext());
        $this->assertSame($prev, $exception->getPrevious());
    }

    public function test_construct_with_null_context(): void
    {
        $exception = new ExtensionRequiredException('t', null);
        $this->assertSame('t', $exception->getMessage());
        $this->assertSame([], $exception->getContext());
    }
}
