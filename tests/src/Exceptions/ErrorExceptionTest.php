<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use ErrorException as BaseException;
use JsonSerializable;
use Kirameki\Core\Exceptions\ErrorException;
use Kirameki\Core\Exceptions\Exceptionable;
use Kirameki\Core\Exceptions\LogicException;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use function array_keys;
use function assert;
use function error_get_last;
use function error_reporting;
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
        $this->assertSame([], $exception->getContext());
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

    public function test_fromErrorGetLast_on_no_error(): void
    {
        $this->expectExceptionMessage('No error found from error_get_last().');
        $this->expectException(LogicException::class);
        ErrorException::fromErrorGetLast();
    }

    public function test_construct(): void
    {
        $exception = new ErrorException('test', E_ERROR, __FILE__, __LINE__);
        $this->assertInstanceOf(BaseException::class, $exception);
        $this->assertInstanceOf(Exceptionable::class, $exception);
        $this->assertInstanceOf(JsonSerializable::class, $exception);
        $this->assertSame([], $exception->getContext());
    }

    public function test_construct_with_context(): void
    {
        $context = ['a' => 1, 'b' => 2];
        $exception = new ErrorException('t', E_ERROR, __FILE__, __LINE__);
        $exception->mergeContext($context);
        $this->assertSame('t', $exception->getMessage());
        $this->assertSame($context, $exception->getContext());
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
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($severity, $exception->getSeverity());
        $this->assertSame($file, $exception->getFile());
        $this->assertSame($line, $exception->getLine());
        $this->assertSame($context, $exception->getContext());
        $this->assertNull($exception->getPrevious());
    }

    public function test_jsonSerialize(): void
    {
        $message = 'z';
        $severity = E_WARNING;
        $context = ['a' => 1];
        $exception = new ErrorException($message, $severity, __FILE__, __LINE__);
        $exception->setContext($context);
        $json = $exception->jsonSerialize();
        $this->assertSame($exception::class, $json['class']);
        $this->assertSame($message, $json['message']);
        $this->assertSame(__FILE__, $json['file']);
        $this->assertIsInt($json['line']);
        $this->assertSame($context, $json['context']);
        $this->assertSame(
            ['class', 'message', 'severity', 'file', 'line', 'context'],
            array_keys($json),
        );
    }
}
