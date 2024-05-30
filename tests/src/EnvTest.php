<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Env;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Exceptions\KeyNotFoundException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Kirameki\Core\Testing\TestCase;
use function array_keys;
use function array_search;
use function gethostname;
use const INF;

final class EnvTest extends TestCase
{
    public function test_instantiate(): void
    {
        $this->expectExceptionMessage('Cannot instantiate static class: Kirameki\Core\Env');
        $this->expectException(NotSupportedException::class);
        new Env();
    }

    public function test_all(): void
    {
        $all = Env::all();
        $this->assertSame(gethostname(), $all['HOSTNAME']);
        $this->assertSame('/app', $all['PWD']);
        $this->assertIsNumeric($all['SHLVL']);

        // sort order
        $keys = array_keys($all);
        $index1 = array_search('HOME', $keys, true);
        $this->assertGreaterThan($index1, $index2 = array_search('HOSTNAME', $keys, true));
        $this->assertGreaterThan($index2, $index3 = array_search('PWD', $keys, true));
        $this->assertGreaterThan($index3, array_search('SHLVL', $keys, true));
    }

    public function test_all_out_of_order(): void
    {
        $this->assertNotSame(
            array_keys(Env::all()),
            array_keys(Env::all(false)),
        );
    }

    public function test_getBool(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $_ENV['DEBUG'] = 'true';
        $this->assertSame(true, Env::getBool('DEBUG'), 'get true as bool');
        $this->assertSame('true', Env::getString('DEBUG'), 'get true as string');
        $_ENV['DEBUG'] = 'false';
        $this->assertSame(false, Env::getBool('DEBUG'), 'get false as bool');
        $this->assertSame('false', Env::getString('DEBUG'), 'get false as string');
    }

    public function test_getBool_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(KeyNotFoundException::class);
        Env::getBool('DEBUG');
    }

    public function test_getBool_on_int(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $_ENV['DEBUG'] = '1';
        $this->expectExceptionMessage('Expected: DEBUG to be type bool. Got: string.');
        $this->expectException(TypeMismatchException::class);
        Env::getBool('DEBUG');
    }

    public function test_getBoolOrNull(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->assertNull(Env::getBoolOrNull('DEBUG'), 'get null as bool');
        $this->assertNull(Env::getStringOrNull('DEBUG'), 'get null as string');
        $_ENV['DEBUG'] = 'true';
        $this->assertSame(true, Env::getBoolOrNull('DEBUG'), 'get true as bool');
        $this->assertSame('true', Env::getStringOrNull('DEBUG'), 'get true as string');
        $_ENV['DEBUG'] = 'false';
        $this->assertSame(false, Env::getBoolOrNull('DEBUG'), 'get false as bool');
        $this->assertSame('false', Env::getStringOrNull('DEBUG'), 'get false as string');
    }

    public function test_getInt(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $_ENV['DEBUG'] = '0';
        $this->assertSame(0, Env::getInt('DEBUG'), 'get 0 as int');
        $this->assertSame('0', Env::getString('DEBUG'), 'get 0 as string');
        $_ENV['DEBUG'] = '1';
        $this->assertSame(1, Env::getInt('DEBUG'), 'get 1 as int');
        $this->assertSame('1', Env::getString('DEBUG'), 'get 1 as string');
        $_ENV['DEBUG'] = '-1';
        $this->assertSame(-1, Env::getInt('DEBUG'), 'get -1 as int');
        $this->assertSame('-1', Env::getString('DEBUG'), 'get -1 as string');
    }

    public function test_getInt_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(KeyNotFoundException::class);
        Env::getInt('DEBUG');
    }

    public function test_getInt_invalid_format(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->expectExceptionMessage('Expected: DEBUG to be type int. Got: string.');
        $this->expectException(TypeMismatchException::class);
        $_ENV['DEBUG'] = '0a0';
        Env::getInt('DEBUG');
    }

    public function test_getIntOrNull(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->assertNull(Env::getIntOrNull('DEBUG'), 'get null as int');
        $this->assertNull(Env::getStringOrNull('DEBUG'), 'get null as string');
        $_ENV['DEBUG'] = '0';
        $this->assertSame(0, Env::getIntOrNull('DEBUG'), 'get 0 as int');
        $this->assertSame('0', Env::getStringOrNull('DEBUG'), 'get 0 as string');
        $_ENV['DEBUG'] = '1';
        $this->assertSame(1, Env::getIntOrNull('DEBUG'), 'get 1 as int');
        $this->assertSame('1', Env::getStringOrNull('DEBUG'), 'get 1 as string');
        $_ENV['DEBUG'] = '-1';
        $this->assertSame(-1, Env::getIntOrNull('DEBUG'), 'get -1 as int');
        $this->assertSame('-1', Env::getStringOrNull('DEBUG'), 'get -1 as string');
    }

    public function test_getFloat(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $_ENV['DEBUG'] = 'true';
        $this->assertSame(true, Env::getBool('DEBUG'), 'set true as bool');
        $this->assertSame('true', Env::getString('DEBUG'), 'set true as string');
        $_ENV['DEBUG'] = 'false';
        $this->assertSame(false, Env::getBool('DEBUG'), 'set false as bool');
        $this->assertSame('false', Env::getString('DEBUG'), 'set false as string');
        $_ENV['DEBUG'] = '0';
        $this->assertSame(0, Env::getInt('DEBUG'), 'set 0 as int');
        $this->assertSame('0', Env::getString('DEBUG'), 'set 0 as string');
        $_ENV['DEBUG'] = '1';
        $this->assertSame(1, Env::getInt('DEBUG'), 'set 1 as int');
        $this->assertSame('1', Env::getString('DEBUG'), 'set 1 as string');
        $_ENV['DEBUG'] = '-1';
        $this->assertSame(-1, Env::getInt('DEBUG'), 'set 1 as int');
        $this->assertSame('-1', Env::getString('DEBUG'), 'set 1 as string');
        $_ENV['DEBUG'] = '0.0';
        $this->assertSame(0.0, Env::getFloat('DEBUG'), 'set 0 as float');
        $this->assertSame('0.0', Env::getString('DEBUG'), 'set 0 as string');
        $_ENV['DEBUG'] = '1.1';
        $this->assertSame(1.1, Env::getFloat('DEBUG'), 'set 1.1 as float');
        $this->assertSame('1.1', Env::getString('DEBUG'), 'set 1.1 as string');
        $_ENV['DEBUG'] = '-1.1';
        $this->assertSame(-1.1, Env::getFloat('DEBUG'), 'set -1.1 as float');
        $this->assertSame('-1.1', Env::getString('DEBUG'), 'set -1.1 as string');
        $_ENV['DEBUG'] = '-1.1E+15';
        $this->assertSame(-1.1e15, Env::getFloat('DEBUG'), 'set scientific notation as float');
        $this->assertSame('-1.1E+15', Env::getString('DEBUG'), 'set scientific notation as string');
        $_ENV['DEBUG'] = 'NAN';
        $this->assertNan(Env::getFloat('DEBUG'), 'set NAN as float');
        $this->assertSame('NAN', Env::getString('DEBUG'), 'set NAN as string');
        $_ENV['DEBUG'] = 'INF';
        $this->assertSame(INF, Env::getFloat('DEBUG'), 'set INF as float');
        $this->assertSame('INF', Env::getString('DEBUG'), 'set INF as string');
        $_ENV['DEBUG'] = '-INF';
        $this->assertSame(-INF, Env::getFloat('DEBUG'), 'set -INF as float');
        $this->assertSame('-INF', Env::getString('DEBUG'), 'set -INF as string');
    }

    public function test_getFloat_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(KeyNotFoundException::class);
        Env::getFloat('DEBUG');
    }

    public function test_getFloat_invalid_format(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->expectExceptionMessage('Expected: DEBUG to be type float. Got: string.');
        $this->expectException(TypeMismatchException::class);
        $_ENV['DEBUG'] = '0a0.0';
        Env::getFloat('DEBUG');
    }

    public function test_getFloatOrNull(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->assertNull(Env::getFloatOrNull('DEBUG'));
        $this->assertNull(Env::getStringOrNull('DEBUG'));
        $_ENV['DEBUG'] = '1.1';
        $this->assertSame(1.1, Env::getFloatOrNull('DEBUG'));
        $this->assertSame('1.1', Env::getStringOrNull('DEBUG'));
    }

    public function test_getString(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $_ENV['DEBUG'] = 'hi';
        $this->assertSame('hi', Env::getString('DEBUG'), 'get string');
        $_ENV['DEBUG'] = '';
        $this->assertSame('', Env::getString('DEBUG'), 'get empty string');
        $_ENV['DEBUG'] = 'null';
        $this->assertSame('null', Env::getString('DEBUG'), 'get "null" string');
    }

    public function test_getString_on_missing(): void
    {
        $this->expectExceptionMessage('ENV: DEBUG is not defined.');
        $this->expectException(KeyNotFoundException::class);
        Env::getFloat('DEBUG');
    }

    public function test_getStringOrNull(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->assertNull(Env::getStringOrNull('DEBUG'));
        $_ENV['DEBUG'] = 'hi';
        $this->assertSame('hi', Env::getStringOrNull('DEBUG'));
        $_ENV['DEBUG'] = '';
        $this->assertSame('', Env::getStringOrNull('DEBUG'), 'get empty string');
    }

    public function test_exists(): void
    {
        $this->runBeforeTearDown(function() { unset($_ENV['DEBUG']); });

        $this->assertFalse(Env::exists('DEBUG'), 'missing');
        $_ENV['DEBUG'] = '1';
        $this->assertTrue(Env::exists('DEBUG'), 'existing');
    }
}
