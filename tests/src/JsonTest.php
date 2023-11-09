<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTime;
use Kirameki\Core\Exceptions\JsonException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Json;
use Kirameki\Core\Testing\TestCase;
use stdClass;
use Tests\Kirameki\Core\_JsonTest\IntEnum;
use Tests\Kirameki\Core\_JsonTest\NonBackedEnum;
use Tests\Kirameki\Core\_JsonTest\SimpleClass;
use Tests\Kirameki\Core\_JsonTest\StringEnum;
use function substr;

final class JsonTest extends TestCase
{
    public function test_instantiate(): void
    {
        $this->expectExceptionMessage('Cannot instantiate static class: Kirameki\Core\Json');
        $this->expectException(NotSupportedException::class);
        new Json();
    }

    public function test_encode(): void
    {
        $this->assertSame('null', Json::encode(null));
        $this->assertSame('1', Json::encode(1));
        $this->assertSame('9223372036854775807', Json::encode(PHP_INT_MAX));
        $this->assertSame('1.0', Json::encode(1.0));
        $this->assertSame('1.0', Json::encode(1.00));
        $this->assertSame('0.3333333333333333', Json::encode(1/3));
        $this->assertSame('true', Json::encode(true));
        $this->assertSame('false', Json::encode(false));
        $this->assertSame('""', Json::encode(''));
        $this->assertSame('"ascii"', Json::encode('ascii'));
        $this->assertSame('"あいう"', Json::encode('あいう'));
        $this->assertSame('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"', Json::encode('🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        $this->assertSame('"<\\\\>"', Json::encode('<\\>'));
        $this->assertSame('[]', Json::encode([]));
        $this->assertSame('[1,2]', Json::encode([1, 2]));
        $this->assertSame("[\n    1,\n    2\n]", Json::encode([1, 2], formatted: true));
        $this->assertSame('{"1":1}', Json::encode(['1' => 1]));
        $this->assertSame('{}', Json::encode(new stdClass()));
        $this->assertSame('{"b":true,"i":1,"f":1.0}', Json::encode(new SimpleClass()));
        $this->assertSame('{"date":"2021-02-02 00:00:00.000000","timezone_type":3,"timezone":"UTC"}', Json::encode(new DateTime('2021-02-02')));
        $this->assertSame('1', Json::encode(IntEnum::One));
        $this->assertSame('"1"', Json::encode(StringEnum::One));

        // edge case: -0 (int) will be 0 but -0.0 (float) will convert to `-0.0`
        $this->assertSame('0', Json::encode(-0));
        $this->assertSame('-0.0', Json::encode(-0.0));

        // edge case: list and assoc mixed will result in assoc with string key
        $this->assertSame('{"0":1,"a":2}', Json::encode([1, 'a' => 2]));

        // edge case: null is changed to ""
        $this->assertSame('{"":1}', Json::encode([null => 1]));

        // edge case: Closure is changed to "{}"
        $this->assertSame('{}', Json::encode(static fn() => 1));
    }

    public function test_encode_invalid_string(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');
        Json::encode(substr('あ', 0, 1));
    }

    public function test_encode_INF_not_supported(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Inf and NaN cannot be JSON encoded');
        Json::encode(INF);
    }

    public function test_encode_NAN_not_supported(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Inf and NaN cannot be JSON encoded');
        Json::encode(NAN);
    }

    public function test_encode_non_backed_enum_not_supported(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Non-backed enums have no default serialization');
        Json::encode(NonBackedEnum::One);
    }

    public function test_decode(): void
    {
        $this->assertSame(null, Json::decode('null'));
        $this->assertSame(1, Json::decode('1'));
        self::assertSame(1.0, Json::decode('1.0'));
        self::assertSame(true, Json::decode('true'));
        self::assertSame(false, Json::decode('false'));
        self::assertSame('', Json::decode('""'));
        self::assertSame('ascii', Json::decode('"ascii"'));
        self::assertSame('あいう', Json::decode('"あいう"'));
        self::assertSame('あいう', Json::decode('"\u3042\u3044\u3046"'));
        self::assertSame('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Json::decode('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"'));
        self::assertSame([], Json::decode('[]'));
        self::assertSame([1, 2], Json::decode('[1,2]'));
        self::assertSame([1, 2], Json::decode("[1,\n\t2]"));
        $emptyObject = Json::decode('{}');
        self::assertIsObject($emptyObject);
        self::assertSame([], (array) $emptyObject);
        self::assertSame(1, Json::decode('{"0":1}')->{"0"});
        self::assertSame(1, Json::decode('{"1":1}')->{"1"});
        self::assertSame([1, 2], Json::decode('{"a":[1,2]}')->a);
    }

    public function test_decode_invalid_string(): void
    {
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');
        $this->expectException(JsonException::class);
        Json::decode(substr('あ', 0, 1));
    }

    public function test_validate(): void
    {
        self::assertTrue(Json::validate('null'));
        self::assertTrue(Json::validate('1'));
        self::assertTrue(Json::validate('1.0'));
        self::assertTrue(Json::validate('true'));
        self::assertTrue(Json::validate('false'));
        self::assertTrue(Json::validate('""'));
        self::assertTrue(Json::validate('"ascii"'));
        self::assertTrue(Json::validate('"あいう"'));
        self::assertTrue(Json::validate('"\u3042\u3044\u3046"'));
        self::assertTrue(Json::validate('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"'));
        self::assertTrue(Json::validate('[]'));
        self::assertTrue(Json::validate('[1,2]'));
        self::assertTrue(Json::validate("[1,\n\t2]"));
        self::assertTrue(Json::validate('{}'));
        self::assertTrue(Json::validate('{"0":1}'));
        self::assertTrue(Json::validate('{"1":1}'));
        self::assertTrue(Json::validate('{"a":[1,2]}'));
        self::assertFalse(Json::validate(''));
        self::assertFalse(Json::validate('{'));
        self::assertFalse(Json::validate('['));
        self::assertFalse(Json::validate('{a: 1}'));
        self::assertFalse(Json::validate('{"a"}'));
    }
}
