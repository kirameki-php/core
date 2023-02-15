<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTime;
use Kirameki\Core\Exceptions\JsonException;
use Kirameki\Core\Json;
use stdClass;
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
        self::assertSame('null', Json::encode(null));
        self::assertSame('1', Json::encode(1));
        self::assertSame('9223372036854775807', Json::encode(PHP_INT_MAX));
        self::assertSame('1.0', Json::encode(1.0));
        self::assertSame('1.0', Json::encode(1.00));
        self::assertSame('0.3333333333333333', Json::encode(1/3));
        self::assertSame('true', Json::encode(true));
        self::assertSame('false', Json::encode(false));
        self::assertSame('""', Json::encode(''));
        self::assertSame('"ascii"', Json::encode('ascii'));
        self::assertSame('"あいう"', Json::encode('あいう'));
        self::assertSame('"🏴󠁧󠁢󠁳󠁣󠁴󠁿"', Json::encode('🏴󠁧󠁢󠁳󠁣󠁴󠁿'));
        self::assertSame('"<\\\\>"', Json::encode('<\\>'));
        self::assertSame('[]', Json::encode([]));
        self::assertSame('[1,2]', Json::encode([1, 2]));
        self::assertSame("[\n    1,\n    2\n]", Json::encode([1, 2], formatted: true));
        self::assertSame('{"1":1}', Json::encode(['1' => 1]));
        self::assertSame('{}', Json::encode(new stdClass()));
        self::assertSame('{"b":true,"i":1,"f":1.0}', Json::encode(new SimpleClass()));
        self::assertSame('{"date":"2021-02-02 00:00:00.000000","timezone_type":3,"timezone":"UTC"}', Json::encode(new DateTime('2021-02-02')));
        self::assertSame('1', Json::encode(IntEnum::One));
        self::assertSame('"1"', Json::encode(StringEnum::One));

        // edge case: -0 (int) will be 0 but -0.0 (float) will convert to `-0.0`
        self::assertSame('0', Json::encode(-0));
        self::assertSame('-0.0', Json::encode(-0.0));

        // edge case: list and assoc mixed will result in assoc with string key
        self::assertSame('{"0":1,"a":2}', Json::encode([1, 'a' => 2]));

        // edge case: null is changed to ""
        self::assertSame('{"":1}', Json::encode([null => 1]));

        // edge case: Closure is changed to "{}"
        self::assertSame('{}', Json::encode(static fn() => 1));
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
        self::assertSame(null, Json::decode('null'));
        self::assertSame(1, Json::decode('1'));
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
