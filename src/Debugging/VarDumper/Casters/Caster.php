<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Casters;

use Kirameki\Core\Debugging\VarDumper\Processors\Processor;

abstract class Caster
{
    /**
     * @param Processor $processor
     * @param object $var
     * @param int $id
     * @return string
     */
    abstract public function cast(Processor $processor, object $var, int $id): string;
}
