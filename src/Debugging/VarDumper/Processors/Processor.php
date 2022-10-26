<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper\Processors;

use Closure;
use Kirameki\Core\Debugging\VarDumper\Casters\Caster;
use function array_key_exists;

interface Processor
{
    public function output(string $string): void;

    public function indent(string $string, int $depth): string;

    public function line(string $string, int $depth): string;

    public function eol(): string;

    public function type(string $type): string;

    public function scalar(mixed $value): string;

    public function objectId(string $id): string;

    public function parameterKey(int|string $key): string;

    public function parameterDelimiter(string $delimiter): string;
}
