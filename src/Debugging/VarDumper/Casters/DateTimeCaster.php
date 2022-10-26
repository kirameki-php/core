<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Casters;

use DateTime;
use Kirameki\Core\Debugging\VarDumper\Processors\Processor;

class DateTimeCaster extends Caster
{
    /**
     * @param Processor $processor
     * @param DateTime $var
     * @param int $id
     * @return string
     */
    public function cast(Processor $processor, object $var, int $id): string
    {
        return
            $processor->type($var::class) . ' ' .
            $processor->scalar($var->format('Y-m-d H:i:s.u T (P)')) . ' ' .
            $processor->objectId("#$id");
    }
}
