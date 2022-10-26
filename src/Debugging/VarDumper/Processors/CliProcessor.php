<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Processors;

use Kirameki\Core\Cli\Ansi;
use Kirameki\Core\Cli\Ansi\Color;
use function str_repeat;
use const PHP_EOL;

class CliProcessor implements Processor
{
    public function __construct(
        protected Ansi $ansi = new Ansi(),
        protected string $indentation = '  ',
    )
    {
    }

    public function output(string $string): void
    {
        $eol = $this->eol();
        echo "{$eol}{$string}{$eol}";
    }

    /**
     * @param string $type
     * @return string
     */
    public function type(string $type): string
    {
        return $this->withColor($type, Color::DarkCyan);
    }

    public function scalar(mixed $value): string
    {
        return $this->withColor($value, Color::LightGoldenrod3);
    }

    /**
     * @param int|string $key
     * @return string
     */
    public function parameterKey(int|string $key): string
    {
        return is_int($key)
            ? $this->withColor((string) $key, Color::Violet)
            : $this->withColor($key, Color::CornflowerBlue);
    }

    /**
     * @param string $delimiter
     * @return string
     */
    public function parameterDelimiter(string $delimiter): string
    {
        return $this->withColor($delimiter, Color::Gray);
    }

    /**
     * @param string $id
     * @return string
     */
    public function objectId(string $id): string
    {
        return $this->withColor($id, Color::Gray);
    }

    public function comment(string $value): string
    {
        return $value;
    }

    /**
     * @param string $string
     * @param int $depth
     * @return string
     */
    public function line(string $string, int $depth): string
    {
        return $this->indent($string, $depth) . $this->eol();
    }

    /**
     * @param string $string
     * @param int $depth
     * @return string
     */
    public function indent(string $string, int $depth): string
    {
        return str_repeat($this->indentation, $depth) . $string;
    }

    /**
     * @return string
     */
    public function eol(): string
    {
        return PHP_EOL;
    }

    /**
     * @param string $value
     * @param Ansi\Color $color
     * @return string
     */
    public function withColor(string $value, Ansi\Color $color): string
    {
        return $this->ansi
            ->buffer()
            ->foreground($color)
            ->text($value)
            ->noStyle()
            ->toString();
    }
}
