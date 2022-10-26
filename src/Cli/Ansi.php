<?php declare(strict_types=1);

namespace Kirameki\Core\Cli;

use BackedEnum;
use Kirameki\Core\Cli\Ansi\C0;
use Kirameki\Core\Cli\Ansi\Color;
use Kirameki\Core\Cli\Ansi\Csi;
use Kirameki\Core\Cli\Ansi\Csi\Cursor;
use Kirameki\Core\Cli\Ansi\Csi\Erase;
use Kirameki\Core\Cli\Ansi\Csi\Scroll;
use Kirameki\Core\Cli\Ansi\Fe;
use Kirameki\Core\Cli\Ansi\Sgr;
use Stringable;
use Webmozart\Assert\Assert;
use function compact;
use function fread;
use function fwrite;
use function implode;
use function shell_exec;
use function sscanf;
use function system;
use function trim;
use const STDIN;
use const STDOUT;

class Ansi
{
    /**
     * @var list<string>
     */
    protected array $sequences = [];

    /**
     * @var bool
     */
    protected bool $buffering = false;

    /**
     * @param int|string|Stringable|BackedEnum ...$sequences
     * @return $this
     */
    public function sequence(int|string|Stringable|BackedEnum ...$sequences): static
    {
        foreach ($sequences as $sequence) {
            $this->sequences[] = match (true) {
                $sequence instanceof BackedEnum => (string)$sequence->value,
                $sequence instanceof Stringable => $sequence->__toString(),
                default => (string)$sequence,
            };
        }

        if (!$this->buffering) {
            $this->flush();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function buffer(): static
    {
        $this->buffering = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBuffering(): bool
    {
        return $this->buffering;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text(string $text): static
    {
        return $this->sequence($text);
    }

    /**
     * @param string $text
     * @return $this
     */
    public function line(string $text): static
    {
        return $this->text($text)->noStyle()->carriageReturn()->lineFeed();
    }

    /**
     * @return $this
     */
    public function bell(): static
    {
        return $this->sequence(C0::Bell);
    }

    /**
     * @return $this
     */
    public function backspace(): static
    {
        return $this->sequence(C0::Backspace);
    }

    /**
     * @return $this
     */
    public function tab(): static
    {
        return $this->sequence(C0::Tab);
    }

    /**
     * @return $this
     */
    public function lineFeed(): static
    {
        return $this->sequence(C0::LineFeed);
    }

    /**
     * @return $this
     */
    public function carriageReturn(): static
    {
        return $this->sequence(C0::CarriageReturn);
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorUp(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::up($cells))
            : $this;
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorDown(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::down($cells))
            : $this;
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorForward(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::forward($cells))
            : $this;
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorBack(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::back($cells))
            : $this;
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorNextLine(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::nextLine($cells))
            : $this;
    }

    /**
     * @param int $cells
     * @return $this
     */
    public function cursorPreviousLine(int $cells = 1): static
    {
        Assert::greaterThanEq($cells, 0);
        return $cells > 0
            ? $this->sequence(C0::Escape, Fe::CSI, Cursor::prevLine($cells))
            : $this;
    }

    /**
     * @param int $row
     * @param int $column
     * @return $this
     */
    public function cursorPosition(int $row, int $column): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Cursor::position($row, $column));
    }

    /**
     * @return $this
     */
    public function eraseScreen(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::screen());
    }

    /**
     * @return $this
     */
    public function eraseToEndOfScreen(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::toEndOfScreen());
    }

    /**
     * @return $this
     */
    public function eraseFromStartOfScreen(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::fromStartOfScreen());
    }

    /**
     * @return $this
     */
    public function eraseSavedLines(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::savedLines());
    }

    /**
     * @return $this
     */
    public function eraseLine(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::line());
    }

    /**
     * @return $this
     */
    public function eraseToEndOfLine(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::toEndOfLine());
    }

    /**
     * @return $this
     */
    public function eraseFromStartOfLine(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Erase::fromStartOfLine());
    }

    /**
     * New lines are added at the bottom.
     *
     * @param int $lines
     * @return $this
     */
    public function scrollUp(int $lines = 1): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Scroll::up($lines));
    }

    /**
     * New lines are added at the bottom.
     *
     * @param int $lines
     * @return $this
     */
    public function scrollDown(int $lines = 1): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Scroll::down($lines));
    }

    /**
     * @see https://en.wikipedia.org/wiki/ANSI_escape_code#8-bit
     * @return $this
     */
    public function foreground(Color $color): static
    {
        return $this->color($color, Sgr::SetForegroundColor);
    }

    /**
     * @see https://en.wikipedia.org/wiki/ANSI_escape_code#8-bit
     * @param Color $color
     * @return $this
     */
    public function background(Color $color): static
    {
        return $this->color($color, Sgr::SetBackgroundColor);
    }

    /**
     * @param Color $color
     * @param Sgr $section
     * @return $this
     */
    public function color(Color $color, Sgr $section): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, $section, $color, Csi::Sgr);
    }

    /**
     * @return $this
     */
    public function noStyle(): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, Sgr::Reset, Csi::Sgr);
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function bold(bool $toggle = true): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, ($toggle ? Sgr::Bold : Sgr::NormalIntensity));
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function italic(bool $toggle = true): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, ($toggle ? Sgr::Italic : Sgr::NormalIntensity));
    }

    /**
     * @param bool $toggle
     * @return $this
     */
    public function underline(bool $toggle = true): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, ($toggle ? Sgr::Underline : Sgr::NotUnderlined));
    }

    /**
     * @return $this
     */
    public function blink(bool $toggle = true): static
    {
        return $this->sequence(C0::Escape, Fe::CSI, ($toggle ? Sgr::Blink : Sgr::NotBlinking));
    }

    /**
     * @return array{ row: int, column: int }
     */
    public function getDeviceStatusReport(): array
    {
        // backup original stty mode
        $stty = trim((string) shell_exec('stty -g'));

        // set stty mode
        system("stty -icanon -echo");

        try {
            $this->sequence(C0::Escape, Fe::CSI, Csi::DeviceStatusReport)->flush();
            $code = trim((string) fread(STDIN, 100));
            sscanf($code, "\e[%d;%dR", $row, $column);
            return compact('row', 'column');
        }
        finally {
            system("stty $stty");
        }
    }

    /**
     * @return array{ row: int, column: int }
     */
    public function getTerminalSize(): array
    {
        $current = $this->getDeviceStatusReport();

        // Move as far away as it can to determine the max cursor position.
        $this->cursorPosition(9999, 9999);

        // get the max position which is the size of the terminal.
        $size = $this->getDeviceStatusReport();

        // Restore cursor position.
        $this->cursorPosition(...$current);

        return $size;
    }

    /**
     * @return $this
     */
    public function flush(): static
    {
        return $this->flushTo(STDOUT);
    }

    /**
     * @param resource $stream
     * @return $this
     */
    public function flushTo($stream): static
    {
        fwrite($stream, $this->toString());
        return $this;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $string = implode('', $this->sequences);
        $this->sequences = [];
        $this->buffering = false;
        return $string;
    }
}
