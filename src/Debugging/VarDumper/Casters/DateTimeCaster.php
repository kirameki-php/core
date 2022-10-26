<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Casters;

use DateTime;

class DateTimeCaster extends Caster
{
    /**
     * @param DateTime $var
     * @param int $id
     * @return string
     */
    public function cast(object $var, int $id): string
    {
        return
            $this->decorator->type($var::class) . ' ' .
            $this->decorator->scalar($var->format('Y-m-d H:i:s.u T (P)')) . ' ' .
            $this->decorator->comment("#$id");
    }
}
