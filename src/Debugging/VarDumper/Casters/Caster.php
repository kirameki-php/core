<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Casters;

use Kirameki\Core\Debugging\VarDumper\Decorators\Decorator;
use Kirameki\Core\Debugging\VarDumper\Formatter;

abstract class Caster
{
    public function __construct(
        protected Decorator $decorator,
        protected Formatter $formatter,
    )
    {
    }

    /**
     * @param object $var
     * @param int $id
     * @return string
     */
    abstract public function cast(object $var, int $id): string;
}
