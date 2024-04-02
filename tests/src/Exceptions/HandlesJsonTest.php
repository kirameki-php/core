<?php declare(strict_types=1);

namespace Tests\Kirameki\Core\Exceptions;

use Kirameki\Core\Exceptions\Exception;
use RuntimeException;
use function array_keys;

final class HandlesJsonTest extends TestCase
{
    public function test_jsonSerialize(): void
    {
        $message = 'z';
        $context = ['a' => 1];
        $code = 100;
        $prev = new RuntimeException();
        $exception = new Exception($message, $context, $code, $prev);
        $json = $exception->jsonSerialize();
        $this->assertSame($exception::class, $json['class']);
        $this->assertSame($message, $json['message']);
        $this->assertSame($code, $json['code']);
        $this->assertSame(__FILE__, $json['file']);
        $this->assertIsInt($json['line']);
        $this->assertSame($context, $json['context']);
        $this->assertSame(
            ['class', 'message', 'code', 'file', 'line', 'trace'],
            array_keys($json['previous']),
        );
    }

    public function test_jsonSerialize_minimum(): void
    {
        $exception = new Exception();
        $json = $exception->jsonSerialize();
        $this->assertSame($exception::class, $json['class']);
        $this->assertSame('', $json['message']);
        $this->assertSame(0, $json['code']);
        $this->assertSame(__FILE__, $json['file']);
        $this->assertIsInt($json['line']);
        $this->assertSame([], $json['context']);
    }
}
