<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTime;
use Kirameki\Core\Env;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Testing\TestCase;
use function array_keys;
use function array_search;
use function gethostname;
use const INF;
use const NAN;

final class EnvTest extends TestCase
{
    public function test_all(): void
    {
        $all = Env::all();
        $this->assertSame('UTF-8', $all['CHARSET']);
        $this->assertSame('/root', $all['HOME']);
        $this->assertSame(gethostname(), $all['HOSTNAME']);
        $this->assertSame('C.UTF-8', $all['LANG']);
        $this->assertSame('/app', $all['PWD']);
        $this->assertSame('Asia/Tokyo', $all['TZ']);

        // sort order
        $keys = array_keys($all);
        $index1 = array_search('CHARSET', $keys, true);
        $this->assertGreaterThan($index1, $index2 = array_search('HOME', $keys, true));
        $this->assertGreaterThan($index2, $index3 = array_search('HOSTNAME', $keys, true));
        $this->assertGreaterThan($index3, $index4 = array_search('LANG', $keys, true));
        $this->assertGreaterThan($index4, $index5 = array_search('PWD', $keys, true));
        $this->assertGreaterThan($index5, array_search('TZ', $keys, true));
    }

    public function test_all_out_of_order(): void
    {
        $keys = array_keys(Env::all(false));
        $this->assertLessThan(
            array_search('LANG', $keys, true),
            array_search('TZ', $keys, true),
        );
    }

    public function test_getBool(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', true);
        $this->assertSame(true, Env::getBool('DEBUG'), 'get true as bool');
        $this->assertSame('true', Env::getString('DEBUG'), 'get true as string');
        Env::set('DEBUG', false);
        $this->assertSame(false, Env::getBool('DEBUG'), 'get false as bool');
        $this->assertSame('false', Env::getString('DEBUG'), 'get false as string');
    }

    public function test_getBool_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(InvalidArgumentException::class);
        Env::getBool('DEBUG');
    }

    public function test_getBool_on_int(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', 1);
        $this->expectExceptionMessage('Expected: DEBUG to be type bool. Got: string.');
        $this->expectException(NotSupportedException::class);
        Env::getBool('DEBUG');
    }

    public function test_getBoolOrNull(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->assertNull(Env::getBoolOrNull('DEBUG'), 'get null as bool');
        $this->assertNull(Env::getStringOrNull('DEBUG'), 'get null as string');
        Env::set('DEBUG', true);
        $this->assertSame(true, Env::getBoolOrNull('DEBUG'), 'get true as bool');
        $this->assertSame('true', Env::getStringOrNull('DEBUG'), 'get true as string');
        Env::set('DEBUG', false);
        $this->assertSame(false, Env::getBoolOrNull('DEBUG'), 'get false as bool');
        $this->assertSame('false', Env::getStringOrNull('DEBUG'), 'get false as string');
    }

    public function test_getInt(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', 0);
        $this->assertSame(0, Env::getInt('DEBUG'), 'get 0 as int');
        $this->assertSame('0', Env::getString('DEBUG'), 'get 0 as string');
        Env::set('DEBUG', 1);
        $this->assertSame(1, Env::getInt('DEBUG'), 'get 1 as int');
        $this->assertSame('1', Env::getString('DEBUG'), 'get 1 as string');
        Env::set('DEBUG', -1);
        $this->assertSame(-1, Env::getInt('DEBUG'), 'get -1 as int');
        $this->assertSame('-1', Env::getString('DEBUG'), 'get -1 as string');
    }

    public function test_getInt_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(InvalidArgumentException::class);
        Env::getInt('DEBUG');
    }

    public function test_getInt_invalid_format(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->expectExceptionMessage('Expected: DEBUG to be type int. Got: string.');
        $this->expectException(NotSupportedException::class);
        Env::set('DEBUG', '0a0');
        Env::getInt('DEBUG');
    }

    public function test_getIntOrNull(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->assertNull(Env::getIntOrNull('DEBUG'), 'get null as int');
        $this->assertNull(Env::getStringOrNull('DEBUG'), 'get null as string');
        Env::set('DEBUG', 0);
        $this->assertSame(0, Env::getIntOrNull('DEBUG'), 'get 0 as int');
        $this->assertSame('0', Env::getStringOrNull('DEBUG'), 'get 0 as string');
        Env::set('DEBUG', 1);
        $this->assertSame(1, Env::getIntOrNull('DEBUG'), 'get 1 as int');
        $this->assertSame('1', Env::getStringOrNull('DEBUG'), 'get 1 as string');
        Env::set('DEBUG', -1);
        $this->assertSame(-1, Env::getIntOrNull('DEBUG'), 'get -1 as int');
        $this->assertSame('-1', Env::getStringOrNull('DEBUG'), 'get -1 as string');
    }

    public function test_getFloat(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', true);
        $this->assertSame(true, Env::getBool('DEBUG'), 'set true as bool');
        $this->assertSame('true', Env::getString('DEBUG'), 'set true as string');
        Env::set('DEBUG', false);
        $this->assertSame(false, Env::getBool('DEBUG'), 'set false as bool');
        $this->assertSame('false', Env::getString('DEBUG'), 'set false as string');
        Env::set('DEBUG', 0);
        $this->assertSame(0, Env::getInt('DEBUG'), 'set 0 as int');
        $this->assertSame('0', Env::getString('DEBUG'), 'set 0 as string');
        Env::set('DEBUG', 1);
        $this->assertSame(1, Env::getInt('DEBUG'), 'set 1 as int');
        $this->assertSame('1', Env::getString('DEBUG'), 'set 1 as string');
        Env::set('DEBUG', -1);
        $this->assertSame(-1, Env::getInt('DEBUG'), 'set 1 as int');
        $this->assertSame('-1', Env::getString('DEBUG'), 'set 1 as string');
        Env::set('DEBUG', 0.0);
        $this->assertSame(0.0, Env::getFloat('DEBUG'), 'set 0 as float');
        $this->assertSame('0', Env::getString('DEBUG'), 'set 0 as string');
        Env::set('DEBUG', 1.1);
        $this->assertSame(1.1, Env::getFloat('DEBUG'), 'set 1.1 as float');
        $this->assertSame('1.1', Env::getString('DEBUG'), 'set 1.1 as string');
        Env::set('DEBUG', -1.1);
        $this->assertSame(-1.1, Env::getFloat('DEBUG'), 'set -1.1 as float');
        $this->assertSame('-1.1', Env::getString('DEBUG'), 'set -1.1 as string');
        Env::set('DEBUG', -1.1e15);
        $this->assertSame(-1.1e15, Env::getFloat('DEBUG'), 'set scientific notation as float');
        $this->assertSame('-1.1E+15', Env::getString('DEBUG'), 'set scientific notation as string');
        Env::set('DEBUG', NAN);
        $this->assertNan(Env::getFloat('DEBUG'), 'set NAN as float');
        $this->assertSame('NAN', Env::getString('DEBUG'), 'set NAN as string');
        Env::set('DEBUG', INF);
        $this->assertSame(INF, Env::getFloat('DEBUG'), 'set INF as float');
        $this->assertSame('INF', Env::getString('DEBUG'), 'set INF as string');
        Env::set('DEBUG', -INF);
        $this->assertSame(-INF, Env::getFloat('DEBUG'), 'set -INF as float');
        $this->assertSame('-INF', Env::getString('DEBUG'), 'set -INF as string');
    }

    public function test_getFloat_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(InvalidArgumentException::class);
        Env::getFloat('DEBUG');
    }

    public function test_getFloat_invalid_format(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->expectExceptionMessage('Expected: DEBUG to be type float. Got: string.');
        $this->expectException(NotSupportedException::class);
        Env::set('DEBUG', '0a0.0');
        Env::getFloat('DEBUG');
    }

    public function test_getFloatOrNull(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->assertNull(Env::getFloatOrNull('DEBUG'));
        $this->assertNull(Env::getStringOrNull('DEBUG'));
        Env::set('DEBUG', 1.1);
        $this->assertSame(1.1, Env::getFloatOrNull('DEBUG'));
        $this->assertSame('1.1', Env::getStringOrNull('DEBUG'));
    }

    public function test_getString(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', 'hi');
        $this->assertSame('hi', Env::getString('DEBUG'), 'get string');
        Env::set('DEBUG', '');
        $this->assertSame('', Env::getString('DEBUG'), 'get empty string');
        Env::set('DEBUG', 'null');
        $this->assertSame('null', Env::getString('DEBUG'), 'get "null" string');
    }

    public function test_getString_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(InvalidArgumentException::class);
        Env::getFloat('DEBUG');
    }

    public function test_getStringOrNull(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        $this->assertNull(Env::getStringOrNull('DEBUG'));
        Env::set('DEBUG', 'hi');
        $this->assertSame('hi', Env::getStringOrNull('DEBUG'));
        Env::set('DEBUG', '');
        $this->assertSame('', Env::getStringOrNull('DEBUG'), 'get empty string');
    }

    public function test_set(): void
    {
        $this->runBeforeTearDown(fn() => Env::delete('DEBUG'));

        Env::set('DEBUG', true);
        $this->assertSame('true', Env::getString('DEBUG'), 'true');
        Env::set('DEBUG', false);
        $this->assertSame('false', Env::getString('DEBUG'), 'false');
        Env::set('DEBUG', 0);
        $this->assertSame('0', Env::getString('DEBUG'), 'int zero');
        Env::set('DEBUG', 1);
        $this->assertSame('1', Env::getString('DEBUG'), 'int positive');
        Env::set('DEBUG', -1);
        $this->assertSame('-1', Env::getString('DEBUG'), 'int negative');
        Env::set('DEBUG', 1.1);
        $this->assertSame('1.1', Env::getString('DEBUG'), 'float positive');
        Env::set('DEBUG', -1.1);
        $this->assertSame('-1.1', Env::getString('DEBUG'), 'float negative');
        Env::set('DEBUG', NAN);
        $this->assertSame('NAN', Env::getString('DEBUG'), 'NAN');
        Env::set('DEBUG', INF);
        $this->assertSame('INF', Env::getString('DEBUG'), 'INF');
        Env::set('DEBUG', -INF);
        $this->assertSame('-INF', Env::getString('DEBUG'), '-INF');
        Env::set('DEBUG', 'hi');
        $this->assertSame('hi', Env::getString('DEBUG'), 'hi');
        Env::set('DEBUG', '');
        $this->assertSame('', Env::getString('DEBUG'), 'empty string');
        Env::set('DEBUG', 'null');
        $this->assertSame('null', Env::getString('DEBUG'), 'null string');
        $this->assertSame('null', Env::getStringOrNull('DEBUG'), 'null string');
    }

    public function test_set_null_invalid(): void
    {
        $this->expectExceptionMessage('Type: NULL cannot be converted to string.');
        $this->expectException(NotSupportedException::class);
        Env::set('DEBUG', null);
    }

    public function test_set_array_invalid(): void
    {
        $this->expectExceptionMessage('Type: array cannot be converted to string.');
        $this->expectException(NotSupportedException::class);
        Env::set('DEBUG', []);
    }

    public function test_set_object_invalid(): void
    {
        $this->expectExceptionMessage('Type: object cannot be converted to string.');
        $this->expectException(NotSupportedException::class);
        Env::set('DEBUG', new DateTime());
    }
}
