<?php declare(strict_types=1);

namespace Kirameki\Core\Cli\Ansi\Csi;

use Kirameki\Core\Cli\Ansi\Csi;

final class Erase extends Sequences
{
    /**
     * @return static
     */
    public static function toEndOfScreen(): self
    {
        return new self('0', Csi::EraseInDisplay);
    }

    /**
     * @return static
     */
    public static function fromStartOfScreen(): self
    {
        return new self('1', Csi::EraseInDisplay);
    }

    /**
     * @return static
     */
    public static function screen(): self
    {
        return new self('2', Csi::EraseInDisplay);
    }

    /**
     * @return static
     */
    public static function savedLines(): self
    {
        return new self('2', Csi::EraseInDisplay);
    }

    /**
     * @return static
     */
    public static function toEndOfLine(): self
    {
        return new self('0', Csi::EraseInLine);
    }

    /**
     * @return static
     */
    public static function fromStartOfLine(): self
    {
        return new self('1', Csi::EraseInLine);
    }

    /**
     * @return static
     */
    public static function line(): self
    {
        return new self('2', Csi::EraseInLine);
    }
}
