<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use Kirameki\Core\Exceptions\InvalidTypeException;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Core\Type;
use stdClass;
use Tests\Kirameki\Core\_TypeTest\AbstractClass;
use Tests\Kirameki\Core\_TypeTest\ConcreteClass;
use Tests\Kirameki\Core\_TypeTest\IntersectClass;
use Traversable;

final class TypeTest extends TestCase
{
    public function test_of(): void
    {
        $this->assertSame('int', Type::of(1));
        $this->assertSame('string', Type::of('1'));
        $this->assertSame('float', Type::of(1.0));
        $this->assertSame('bool', Type::of(true));
        $this->assertSame('array', Type::of([]));
        $this->assertSame('stdClass', Type::of(new stdClass()));
        $this->assertSame('null', Type::of(null));
    }

    public function test_is(): void
    {
        $this->assertTrue(Type::is(null, 'null'));
        $this->assertFalse(Type::is(0, 'null'));
        $this->assertFalse(Type::is('', 'null'));
        $this->assertFalse(Type::is('null', 'null'));
        $this->assertFalse(Type::is([], 'null'));
        $this->assertFalse(Type::is([null], 'null'));
        $this->assertFalse(Type::is(new stdClass(), 'null'));
        $this->assertFalse(Type::is(new class() {}, 'null'));
        $this->assertFalse(Type::is(fn() => null, 'null'));

        $this->assertTrue(Type::is(true, 'bool'));
        $this->assertFalse(Type::is(null, 'bool'));
        $this->assertFalse(Type::is(0, 'bool'));
        $this->assertFalse(Type::is('', 'bool'));
        $this->assertFalse(Type::is('true', 'bool'));
        $this->assertFalse(Type::is([], 'bool'));
        $this->assertFalse(Type::is([true], 'int'));
        $this->assertFalse(Type::is(fn() => true, 'bool'));
        $this->assertFalse(Type::is(new stdClass(), 'bool'));
        $this->assertFalse(Type::is(new class() {}, 'bool'));

        $this->assertTrue(Type::is(1, 'int'));
        $this->assertFalse(Type::is(null, 'int'));
        $this->assertFalse(Type::is(1.0, 'int'));
        $this->assertFalse(Type::is('1', 'int'));
        $this->assertFalse(Type::is(true, 'int'));
        $this->assertFalse(Type::is([], 'int'));
        $this->assertFalse(Type::is([1], 'int'));
        $this->assertFalse(Type::is(fn() => 1, 'int'));
        $this->assertFalse(Type::is(new stdClass(), 'int'));
        $this->assertFalse(Type::is(new class() {}, 'int'));

        $this->assertTrue(Type::is(1.0, 'float'));
        $this->assertTrue(Type::is(INF, 'float'));
        $this->assertTrue(Type::is(NAN, 'float'));
        $this->assertFalse(Type::is(null, 'float'));
        $this->assertFalse(Type::is(1, 'float'));
        $this->assertFalse(Type::is('', 'float'));
        $this->assertFalse(Type::is('1.0', 'float'));
        $this->assertFalse(Type::is([], 'float'));
        $this->assertFalse(Type::is([1], 'float'));
        $this->assertFalse(Type::is(fn() => 1.0, 'float'));
        $this->assertFalse(Type::is(new stdClass(), 'float'));
        $this->assertFalse(Type::is(new class() {}, 'float'));

        $this->assertTrue(Type::is('1', 'string'));
        $this->assertTrue(Type::is('', 'string'));
        $this->assertTrue(Type::is(DateTime::class, 'string'));
        $this->assertFalse(Type::is(null, 'string'));
        $this->assertFalse(Type::is(1, 'string'));
        $this->assertFalse(Type::is(1.0, 'string'));
        $this->assertFalse(Type::is(false, 'string'));
        $this->assertFalse(Type::is([], 'string'));
        $this->assertFalse(Type::is([1], 'string'));
        $this->assertFalse(Type::is(fn() => '', 'string'));
        $this->assertFalse(Type::is(new stdClass(), 'string'));
        $this->assertFalse(Type::is(new class() {}, 'string'));

        $this->assertTrue(Type::is([], 'array'));
        $this->assertTrue(Type::is([1], 'array'));
        $this->assertTrue(Type::is(['a' => 1], 'array'));
        $this->assertFalse(Type::is('[]', 'array'));
        $this->assertFalse(Type::is('', 'array'));
        $this->assertFalse(Type::is(null, 'array'));
        $this->assertFalse(Type::is(1, 'array'));
        $this->assertFalse(Type::is(false, 'array'));
        $this->assertFalse(Type::is(new stdClass(), 'array'));
        $this->assertFalse(Type::is(new class() {}, 'array'));

        $this->assertTrue(Type::is(new stdClass(), 'object'));
        $this->assertTrue(Type::is(new class() {}, 'object'));
        $this->assertFalse(Type::is(DateTime::class, 'object'));
        $this->assertFalse(Type::is(null, 'object'));
        $this->assertFalse(Type::is(1, 'object'));
        $this->assertFalse(Type::is(1.0, 'object'));
        $this->assertFalse(Type::is(false, 'object'));
        $this->assertFalse(Type::is('', 'object'));
        $this->assertFalse(Type::is('object', 'object'));
        $this->assertFalse(Type::is([], 'object'));
        $this->assertFalse(Type::is([1], 'object'));
        $this->assertFalse(Type::is(fn() => new stdClass(), 'scalar'));

        $this->assertTrue(Type::is([], 'iterable'));
        $this->assertTrue(Type::is([1], 'iterable'));
        $this->assertTrue(Type::is(['a' => 1], 'iterable'));
        $this->assertTrue(Type::is(new class() implements IteratorAggregate { public function getIterator(): Traversable { yield 1; } }, 'iterable'));
        $this->assertFalse(Type::is('[]', 'iterable'));
        $this->assertFalse(Type::is('', 'iterable'));
        $this->assertFalse(Type::is(null, 'iterable'));
        $this->assertFalse(Type::is(1, 'iterable'));
        $this->assertFalse(Type::is(false, 'iterable'));
        $this->assertFalse(Type::is(new stdClass(), 'iterable'));
        $this->assertFalse(Type::is(fn() => [], 'iterable'));

        $this->assertTrue(Type::is('strlen', 'callable'));
        $this->assertTrue(Type::is(strlen(...), 'callable'));
        $this->assertTrue(Type::is(fn() => true, 'callable'));
        $this->assertTrue(Type::is([$this, 'test_is'], 'callable'));
        $this->assertFalse(Type::is(null, 'callable'));
        $this->assertFalse(Type::is(1, 'callable'));
        $this->assertFalse(Type::is(false, 'callable'));
        $this->assertFalse(Type::is('', 'callable'));
        $this->assertFalse(Type::is('?', 'callable'));
        $this->assertFalse(Type::is([], 'callable'));
        $this->assertFalse(Type::is([1], 'callable'));
        $this->assertFalse(Type::is(new stdClass(), 'callable'));

        $this->assertTrue(Type::is(1, 'scalar'));
        $this->assertTrue(Type::is(1.0, 'scalar'));
        $this->assertTrue(Type::is(false, 'scalar'));
        $this->assertTrue(Type::is('', 'scalar'));
        $this->assertTrue(Type::is('?', 'scalar'));
        $this->assertTrue(Type::is(DateTime::class, 'scalar'));
        $this->assertFalse(Type::is(null, 'scalar'));
        $this->assertFalse(Type::is([], 'scalar'));
        $this->assertFalse(Type::is([1], 'scalar'));
        $this->assertFalse(Type::is(new stdClass(), 'scalar'));
        $this->assertFalse(Type::is(new class() {}, 'scalar'));
        $this->assertFalse(Type::is(fn() => true, 'scalar'));

        // open resource
        $resource = fopen(__FILE__, 'r');
        $this->assertTrue(Type::is($resource, 'resource'));
        if(is_resource($resource)) fclose($resource);
        // closed resource
        $resource = fopen(__FILE__, 'r');
        if(is_resource($resource)) fclose($resource);
        $this->assertTrue(Type::is($resource, 'resource'));
        $this->assertFalse(Type::is(null, 'resource'));
        $this->assertFalse(Type::is(1, 'resource'));
        $this->assertFalse(Type::is(false, 'resource'));
        $this->assertFalse(Type::is('', 'resource'));
        $this->assertFalse(Type::is([], 'resource'));
        $this->assertFalse(Type::is(new stdClass(), 'resource'));
        $this->assertFalse(Type::is(fn() => true, 'resource'));

        $this->assertTrue(Type::is(1, 'mixed'));
        $this->assertTrue(Type::is(1.0, 'mixed'));
        $this->assertTrue(Type::is(false, 'mixed'));
        $this->assertTrue(Type::is('', 'mixed'));
        $this->assertTrue(Type::is('?', 'mixed'));
        $this->assertTrue(Type::is(DateTime::class, 'mixed'));
        $this->assertTrue(Type::is(null, 'mixed'));
        $this->assertTrue(Type::is([], 'mixed'));
        $this->assertTrue(Type::is([1], 'mixed'));
        $this->assertTrue(Type::is(new stdClass(), 'mixed'));
        $this->assertTrue(Type::is(new class() {}, 'mixed'));
        $this->assertTrue(Type::is(fn() => true, 'mixed'));

        $this->assertTrue(Type::is(new DateTime(), DateTimeInterface::class));
        $this->assertTrue(Type::is(new DateTimeImmutable(), DateTimeInterface::class));
        $this->assertTrue(Type::is(new ConcreteClass(), AbstractClass::class));
        $this->assertTrue(Type::is(fn() => true, Closure::class));
        $this->assertFalse(Type::is(1, DateTimeInterface::class));
        $this->assertFalse(Type::is(false, DateTimeInterface::class));
        $this->assertFalse(Type::is('', DateTimeInterface::class));
        $this->assertFalse(Type::is([], DateTimeInterface::class));
        $this->assertFalse(Type::is(new stdClass(), DateTimeInterface::class));
        $this->assertFalse(Type::is(fn() => true, DateTimeInterface::class));

        // union types
        $this->assertTrue(Type::is(1, 'int|null'));
        $this->assertTrue(Type::is(null, 'int|null'));
        $this->assertTrue(Type::is(1, 'int|float'));
        $this->assertTrue(Type::is(1.0, 'int|float'));
        $this->assertTrue(Type::is('1', 'int|float|string'));
        $this->assertTrue(Type::is([], 'array|object'));
        $this->assertTrue(Type::is(new stdClass(), 'array|' . stdClass::class));
        $this->assertFalse(Type::is(false, 'int|null'));
        $this->assertFalse(Type::is('', 'int|float'));

        // intersection types
        $this->assertTrue(Type::is(new IntersectClass(), 'Stringable&Countable'));
        $this->assertFalse(Type::is(1, 'int&null'));

        // mixed types
        $this->assertTrue(Type::is(1, 'int|(Stringable&Countable)'));
        $this->assertTrue(Type::is(new IntersectClass(), 'int|(Stringable&Countable)'));
        $this->assertFalse(Type::is(null, 'int|(Stringable&Countable)'));
        $this->assertFalse(Type::is(1.0, 'int|(Stringable&Countable)'));
        $this->assertFalse(Type::is('', 'int|(Stringable&Countable)'));

        // invalid type but passes since it never gets there.
        $this->assertTrue(Type::is(1, 'int|Stringable&Countable'));
    }

    public function test_of_with_invalid_type(): void
    {
        $this->expectExceptionMessage('Invalid type: hi');
        $this->expectException(InvalidTypeException::class);
        Type::is(1, 'hi|none');
    }

    public function test_of_with_mixed_type_without_parentheses(): void
    {
        $this->expectExceptionMessage('Invalid Type: Stringable&Countable|int (Intersection type missing parentheses?)');
        $this->expectException(InvalidTypeException::class);
        Type::is(1, 'Stringable&Countable|int');
    }
}
