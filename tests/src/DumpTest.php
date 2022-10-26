<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use DateTime;
use Exception;
use Kirameki\Core\Cli\Ansi;
use Kirameki\Core\Debugging\VarDumper;
use Tests\Kirameki\Core\Samples\SimpleClass;
use Tests\Kirameki\Core\Samples\SimpleEnum;
use Tests\Kirameki\Core\Samples\SimpleBackedEnum;
use function dump;
use const INF;
use const NAN;
use const STDIN;

class DumpTest extends TestCase
{
    public function testSomething(): void
    {
        $vars = [
            null,
            -1,
            -0.0,
            1,
            1.1,
            true,
            false,
            NAN,
            INF,
            -INF,
            "text",
            "あいう",
            STDIN,
            new DateTime(),
//            new Exception(),
            new SimpleClass(),
            static fn(string $str): string => 'abc' . $str,
            DateTime::createFromFormat(...),
            strstr(...),
            SimpleEnum::Option1,
            SimpleBackedEnum::Option2,
        ];

        $decorator = new VarDumper\Decorators\CliDecorator();
        $formatter = new VarDumper\Formatter($decorator);
        $vd = new VarDumper($decorator, $formatter);
        $vd->dump($vars);
    }
}
