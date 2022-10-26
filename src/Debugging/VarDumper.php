<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging;

use Closure;
use DateTime;
use Kirameki\Core\Debugging\VarDumper\Casters\Caster;
use Kirameki\Core\Debugging\VarDumper\Casters\DateTimeCaster;
use Kirameki\Core\Debugging\VarDumper\Processors\Processor;
use ReflectionClass;
use ReflectionFunction;
use ReflectionProperty;
use UnitEnum;
use function array_key_exists;
use function count;
use function get_resource_id;
use function get_resource_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;
use function spl_object_id;
use function str_contains;
use function stream_get_meta_data;

class VarDumper
{
    /**
     * @var array<class-string, Closure(): Caster>
     */
    protected array $casters = [];

    /**
     * @var array<class-string, Caster>
     */
    protected array $resolvedCasters = [];

    protected int $depth = 0;

    public function __construct(
        protected Processor $processor,
    )
    {
        $this->casters += [
            DateTime::class => static fn() => new DateTimeCaster(),
        ];
    }

    /**
     * @param mixed $var
     * @return void
     */
    public function dump(mixed $var): void
    {
        $this->depth = 0;
        $string = $this->format($var);
        $this->processor->output($string);
    }

    /**
     * @param mixed $var
     * @return string
     */
    protected function format(mixed $var): string
    {
        return match (true) {
            is_null($var) => $this->formatNull(),
            is_string($var) => $this->formatString($var),
            is_bool($var) => $this->formatBool($var),
            is_int($var) => $this->formatInt($var),
            is_float($var) => $this->formatFloat($var),
            is_object($var) => $this->formatObject($var),
            is_resource($var) => $this->formatResource($var),
            is_array($var) => $this->formatArray($var),
            default => "unknown type",
        };
    }

    /**
     * @return string
     */
    protected function formatNull(): string
    {
        return $this->processor->scalar('null');
    }

    /**
     * @param string $var
     * @return string
     */
    protected function formatString(string $var): string
    {
        return $this->processor->scalar("\"{$var}\"");
    }

    /**
     * @param bool $var
     * @return string
     */
    protected function formatBool(bool $var): string
    {
        return $this->processor->scalar($var ? 'true' : 'false');
    }

    /**
     * @param int $var
     * @return string
     */
    protected function formatInt(int $var): string
    {
        return $this->processor->scalar((string) $var);
    }

    /**
     * @param float $var
     * @return string
     */
    protected function formatFloat(float $var): string
    {
        $string = (string) $var;

        if (str_contains($string, '.') || is_nan($var) || is_infinite($var)) {
            return $this->processor->scalar($string);
        }

        return $this->processor->scalar($string . '.0');
    }

    /**
     * @param object $var
     * @return string
     */
    protected function formatObject(object $var): string
    {
        $id = spl_object_id($var);

        if ($this->hasCaster($var::class)) {
            return $this->callCaster($var, $id);
        }

        if ($var instanceof Closure) {
            return $this->formatClosure($var, $id);
        }

        if ($var instanceof UnitEnum) {
            return $this->formatEnum($var, $id);
        }

        $properties = (new ReflectionClass($var))->getProperties(
            ReflectionProperty::IS_STATIC |
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE,
        );

        $summary =
            $this->processor->type($var::class) . ' ' .
            $this->processor->objectId("#{$id}");
        
        if (count($properties) === 0) {
            return $summary;
        }

        return $this->block(
            "{$summary} {",
            "}",
            function () use ($var, $properties) {
                $string = '';
                foreach ($properties as $prop) {
                    $access = ($prop->getModifiers() & ReflectionProperty::IS_STATIC)
                        ? 'static::'
                        : '';
                    $string .= $this->processor->line(
                        $this->processor->parameterKey($access . '$' . $prop->getName()) .
                        $this->processor->parameterDelimiter(':') . ' ' .
                        $this->format($prop->getValue($var)) .
                        $this->processor->parameterDelimiter(','),
                        $this->depth,
                    );
                }
                return $string;
            },
        );
    }

    /**
     * @param Closure $var
     * @param int $id
     * @return string
     */
    protected function formatClosure(Closure $var, int $id): string
    {
        $ref = new ReflectionFunction($var);

        if ($file = $ref->getFileName()) {
            $startLine = $ref->getStartLine();
            $endLine = $ref->getEndLine();
            $range = ($startLine !== $endLine)
                ? "{$startLine}-{$endLine}"
                : $startLine;
            return
                $this->processor->type($var::class . "@{$file}:{$range}") . ' ' .
                $this->processor->objectId("#{$id}");
        }

        if ($class = $ref->getClosureScopeClass()) {
            return
                $this->processor->type("{$class->getName()}::{$ref->getName()}(...)") . ' ' .
                $this->processor->objectId("#{$id}");
        }

        return
            $this->processor->type("{$ref->getName()}(...)") . ' ' .
            $this->processor->objectId("#{$id}");
    }

    protected function formatEnum(UnitEnum $var, int $id): string
    {
        return
            $this->processor->type($var::class . "::{$var->name}") . ' ' .
            $this->processor->objectId("#{$id}");
    }

    /**
     * @param resource $var
     * @return string
     */
    protected function formatResource(mixed $var): string
    {
        $type = $this->processor->type(get_resource_type($var));
        $id = $this->processor->objectId('@' . get_resource_id($var));

        return $this->block(
            "{$type} {$id} {",
            "}",
            function() use ($var) {
                $string = '';
                foreach (stream_get_meta_data($var) as $key => $val) {
                    $formattedKey = $this->processor->parameterKey($key);
                    $formattedVal = $this->format($val);
                    $arrow = $this->processor->parameterDelimiter(':') . ' ';
                    $string .= $this->processor->line("{$formattedKey}{$arrow}{$formattedVal},", $this->depth);
                }
                return $string;
            },
        );
    }

    /**
     * @param array<mixed> $var
     * @return string
     */
    protected function formatArray(array $var): string
    {
        $start = $this->processor->type('array(' . count($var) . ')') . ' [';
        $end = ']';

        if (count($var) === 0) {
            return "{$start}{$end}";
        }

        return $this->block(
            $start,
            $end,
            function() use ($var) {
                $string = '';
                foreach ($var as $key => $val) {
                    $formattedKey = $this->processor->parameterKey($key);
                    $formattedVal = $this->format($val);
                    $arrow = $this->processor->parameterDelimiter('=>');
                    $string .= $this->processor->line("{$formattedKey} {$arrow} {$formattedVal},", $this->depth);
                }
                return $string;
            },
        );
    }

    /**
     * @param string $start
     * @param string $end
     * @param Closure(): string $block
     * @return string
     */
    protected function block(string $start, string $end, Closure $block): string
    {
        $string = ($this->depth === 0)
            ? $this->processor->line($start, $this->depth)
            : $start . $this->processor->eol();

        ++$this->depth;
        $string .= $block();
        --$this->depth;

        $string .= $this->processor->indent($end, $this->depth);

        if ($this->depth === 0) {
            $string .= $this->processor->eol();
        }

        return $string;
    }

    /**
     * @param class-string $class
     * @return bool
     */
    protected function hasCaster(string $class): bool
    {
        return array_key_exists($class, $this->casters);
    }

    /**
     * @param class-string $class
     * @param Closure(): Caster $callback
     * @return void
     */
    protected function setCaster(string $class, Closure $callback): void
    {
        $this->casters[$class] = $callback;
    }

    /**
     * @param object $var
     * @param int $id
     * @return string
     */
    protected function callCaster(object $var, int $id): string
    {
        $caster = $this->resolvedCasters[$var::class] ??= ($this->casters[$var::class])();
        return $caster->cast($this->processor, $var, $id);
    }
}