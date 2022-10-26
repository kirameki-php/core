<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging;

use Kirameki\Core\Debugging\VarDumper\Decorators\Decorator;
use Kirameki\Core\Debugging\VarDumper\Formatter;

class VarDumper
{
    public function __construct(
        protected Decorator $decorator,
        protected Formatter $formatter,
    )
    {
    }

    /**
     * @param mixed $var
     * @return void
     */
    public function dump(mixed $var): void
    {
        $string = $this->formatter->format($var, 0);
        $this->decorator->output($string);
    }
}