<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use ErrorException as BaseException;
use JsonSerializable;
use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Core\Exceptions\Exceptionable;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use function array_keys;
use function assert;
use function error_get_last;
use const E_ALL;
use const E_ERROR;
use const E_WARNING;

final class ErrorExceptionTest extends TestCase
{
    #[WithoutErrorHandler]
    public function test_fromErrorGetLast(): void
    {
        error_reporting(E_ALL ^ E_WARNING);
        echo $a;
        $exception = ErrorException::fromErrorGetLast();
        $this->assertInstanceOf(ErrorException::class, $exception);
        $this->assertSame('Undefined variable $a', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getSeverity());
        $this->assertSame(__FILE__, $exception->getFile());
        $this->assertSame(__LINE__ - 6, $exception->getLine());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getContext());
        $this->assertNull($exception->getPrevious());
        $this->assertNull(error_get_last());

        echo $a;
        ErrorException::fromErrorGetLast(clearError: false);
        $error = error_get_last();
        $this->assertSame('Undefined variable $a', $error['message'], 'error_get_last() should not be cleared.');

        echo $a;
        $exception = ErrorException::fromErrorGetLast(['a' => 1]);
        $this->assertSame(['a' => 1], $exception->getContext());
    }

    public function test_construct(): void
    {
        $exception = new ErrorException('test', E_ERROR, __FILE__, __LINE__);
        self::assertInstanceOf(BaseException::class, $exception);
        self::assertInstanceOf(Exceptionable::class, $exception);
        self::assertInstanceOf(JsonSerializable::class, $exception);
        self::assertNull($exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $context = ['a' => 1, 'b' => 2];
        $exception = new ErrorException('t', E_ERROR, __FILE__, __LINE__);
        $exception->mergeContext($context);
        self::assertEquals('t', $exception->getMessage());
        self::assertEquals($context, $exception->getContext());
    }

    public function test_construct_with_full_construct(): void
    {
        $message = 't';
        $severity = E_WARNING;
        $file = 'my file';
        $line = 1;
        $context = ['a' => 1];
        $exception = new ErrorException($message, $severity, $file, $line);
        $exception->setContext($context);
        self::assertEquals($message, $exception->getMessage());
        self::assertEquals(0, $exception->getCode());
        self::assertEquals($severity, $exception->getSeverity());
        self::assertEquals($file, $exception->getFile());
        self::assertEquals($line, $exception->getLine());
        self::assertEquals($context, $exception->getContext());
        self::assertNull($exception->getPrevious());
    }

    public function test_jsonSerialize(): void
    {
        $message = 'z';
        $severity = E_WARNING;
        $context = ['a' => 1];
        $exception = new ErrorException($message, $severity, __FILE__, __LINE__);
        $exception->setContext($context);
        $json = $exception->jsonSerialize();
        self::assertEquals($exception::class, $json['class']);
        self::assertEquals($message, $json['message']);
        self::assertEquals(__FILE__, $json['file']);
        self::assertIsInt($json['line']);
        self::assertEquals($context, $json['context']);
        self::assertEquals(
            ['class', 'message', 'severity', 'file', 'line', 'context'],
            array_keys($json),
        );
    }
}
