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
        self::assertEquals($exception::class, $json['class']);
        self::assertEquals($message, $json['message']);
        self::assertEquals($code, $json['code']);
        self::assertEquals(__FILE__, $json['file']);
        self::assertIsInt($json['line']);
        self::assertEquals($context, $json['context']);
        self::assertEquals(
            ['class', 'message', 'code', 'file', 'line', 'trace'],
            array_keys($json['previous']),
        );
    }

    public function test_jsonSerialize_minimum(): void
    {
        $exception = new Exception();
        $json = $exception->jsonSerialize();
        self::assertEquals($exception::class, $json['class']);
        self::assertEquals('', $json['message']);
        self::assertEquals(0, $json['code']);
        self::assertEquals(__FILE__, $json['file']);
        self::assertIsInt($json['line']);
        self::assertNull($json['context']);
    }
}
