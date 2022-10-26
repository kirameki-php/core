<?php declare(strict_types=1);

namespace Kirameki\Core\Cli\Ansi\Csi;

use Kirameki\Core\Cli\Ansi\Csi;

final class Cursor extends Sequences
{
    /**
     * @param int $cells
     * @return static
     */
    public static function up(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorUp);
    }

    /**
     * @param int $cells
     * @return static
     */
    public static function down(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorDown);
    }

    /**
     * @param int $cells
     * @return static
     */
    public static function forward(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorForward);
    }

    /**
     * @param int $cells
     * @return static
     */
    public static function back(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorBack);
    }

    /**
     * @param int $cells
     * @return static
     */
    public static function nextLine(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorNextLine);
    }

    /**
     * @param int $cells
     * @return static
     */
    public static function prevLine(int $cells = 1): self
    {
        return new self((string) $cells, Csi::CursorPrevLine);
    }

    /**
     * @param int $rows
     * @param int $columns
     * @return static
     */
    public static function position(int $rows = 1, int $columns = 1): self
    {
        return new self("$rows;$columns", Csi::CursorPosition);
    }
}
