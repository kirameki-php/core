<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTime;
use JsonException;
use Kirameki\Core\Json;
use Tests\Kirameki\Core\_JsonTest\IntEnum;
use Tests\Kirameki\Core\_JsonTest\NonBackedEnum;
use Tests\Kirameki\Core\_JsonTest\SimpleClass;
use Tests\Kirameki\Core\_JsonTest\StringEnum;
use Tests\Kirameki\Core\Exceptions\TestCase;
use function substr;

class JsonTest extends TestCase
{
    public function test_encode(): void
    {
        self::assertEquals('null', Json::encode(null));
        self::assertEquals('1', Json::encode(1));
        self::assertEquals('9223372036854775807', Json::encode(PHP_INT_MAX));
        self::assertEquals('1.0', Json::encode(1.0));
        self::assertEquals('1.0', Json::encode(1.00));
        self::assertEquals('0.3333333333333333', Json::encode(1/3));
        self::assertEquals('true', Json::encode(true));
        self::assertEquals('false', Json::encode(false));
        self::assertEquals('""', Json::encode(''));
        self::assertEquals('"ascii"', Json::encode('ascii'));
        self::assertEquals('"あいう"', Json::encode('あいう'));
        self::assertEquals('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"', Json::encode('🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertEquals('"<\\\\>"', Json::encode('<\\>'));
        self::assertEquals('[]', Json::encode([]));
        self::assertEquals('[1,2]', Json::encode([1, 2]));
        self::assertEquals("[\n    1,\n    2\n]", Json::encode([1, 2], formatted: true));
        self::assertEquals('{"1":1}', Json::encode(['1' => 1]));
        self::assertEquals('{"b":true,"i":1,"f":1.0}', Json::encode(new SimpleClass()));
        self::assertEquals('{"date":"2021-02-02 00:00:00.000000","timezone_type":3,"timezone":"UTC"}', Json::encode(new DateTime('2021-02-02')));
        self::assertEquals('1', Json::encode(IntEnum::One));
        self::assertEquals('"1"', Json::encode(StringEnum::One));

        // edge case: -0 (int) will be 0 but -0.0 (float) will convert to `-0.0`
        self::assertEquals('0', Json::encode(-0));
        self::assertEquals('-0.0', Json::encode(-0.0));

        // edge case: list and assoc mixed will result in assoc with string key
        self::assertEquals('{"0":1,"a":2}', Json::encode([1, 'a' => 2]));

        // edge case: null is changed to ""
        self::assertEquals('{"":1}', Json::encode([null => 1]));

        // edge case: Closure is changed to "{}"
        self::assertEquals('{}', Json::encode(static fn() => 1));
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
        self::assertEquals(null, Json::decode('null'));
        self::assertEquals(1, Json::decode('1'));
        self::assertEquals(1.0, Json::decode('1.0'));
        self::assertEquals(true, Json::decode('true'));
        self::assertEquals(false, Json::decode('false'));
        self::assertEquals('', Json::decode('""'));
        self::assertEquals('ascii', Json::decode('"ascii"'));
        self::assertEquals('あいう', Json::decode('"あいう"'));
        self::assertEquals('あいう', Json::decode('"\u3042\u3044\u3046"'));
        self::assertEquals('🏴󠁧󠁢󠁳󠁣󠁴󠁿', Json::decode('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"'));
        self::assertEquals([], Json::decode('[]'));
        self::assertEquals([1, 2], Json::decode('[1,2]'));
        self::assertEquals([1, 2], Json::decode("[1,\n\t2]"));
        self::assertEquals([], Json::decode('{}'));
        self::assertEquals([1], Json::decode('{"0":1}'));
        self::assertEquals([1 => 1], Json::decode('{"1":1}'));
        self::assertEquals(['a' => [1, 2]], Json::decode('{"a":[1,2]}'));
    }

    public function test_decode_invalid_string(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');
        Json::encode(substr('あ', 0, 1));
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
