<?php declare(strict_types=1);

namespace Kirameki\Core\Debugging\VarDumper;

use Closure;
use DateTime;
use Kirameki\Core\Debugging\VarDumper\Casters\Caster;
use Kirameki\Core\Debugging\VarDumper\Casters\ClosureCaster;
use Kirameki\Core\Debugging\VarDumper\Casters\DateTimeCaster;
use Kirameki\Core\Debugging\VarDumper\Casters\EnumCaster;
use Kirameki\Core\Debugging\VarDumper\Decorators\Decorator;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;
use function array_key_exists;
use function count;
use function get_resource_id;
use function get_resource_type;
use function is_a;
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

class Formatter
{
    /**
     * @var array<class-string, Closure(): Caster>
     */
    protected array $casterResolvers = [];

    /**
     * @var array<class-string, ?Caster>
     */
    protected array $resolvedCasters = [];

    public function __construct(
        protected Decorator $decorator,
    )
    {
        $this->casterResolvers += [
            Closure::class => fn() => new ClosureCaster($this->decorator, $this),
            DateTime::class => fn() => new DateTimeCaster($this->decorator, $this),
            UnitEnum::class => fn() => new EnumCaster($this->decorator, $this),
        ];
    }

    /**
     * @param mixed $var
     * @param int $depth
     * @return string
     */
    public function format(mixed $var, int $depth): string
    {
        return match (true) {
            is_null($var) => $this->formatNull(),
            is_string($var) => $this->formatString($var),
            is_bool($var) => $this->formatBool($var),
            is_int($var) => $this->formatInt($var),
            is_float($var) => $this->formatFloat($var),
            is_object($var) => $this->formatObject($var, $depth),
            is_array($var) => $this->formatArray($var, $depth),
            is_resource($var) => $this->formatResource($var, $depth),
            default => "unknown type",
        };
    }

    /**
     * @return string
     */
    protected function formatNull(): string
    {
        return $this->decorator->scalar('null');
    }

    /**
     * @param string $var
     * @return string
     */
    protected function formatString(string $var): string
    {
        return $this->decorator->scalar("\"{$var}\"");
    }

    /**
     * @param bool $var
     * @return string
     */
    protected function formatBool(bool $var): string
    {
        return $this->decorator->scalar($var ? 'true' : 'false');
    }

    /**
     * @param int $var
     * @return string
     */
    protected function formatInt(int $var): string
    {
        return $this->decorator->scalar((string) $var);
    }

    /**
     * @param float $var
     * @return string
     */
    protected function formatFloat(float $var): string
    {
        $string = (string) $var;

        if (str_contains($string, '.') || is_nan($var) || is_infinite($var)) {
            return $this->decorator->scalar($string);
        }

        return $this->decorator->scalar($string . '.0');
    }

    /**
     * @param object $var
     * @param int $depth
     * @return string
     */
    protected function formatObject(object $var, int $depth): string
    {
        $id = spl_object_id($var);

        if ($caster = $this->getCaster($var)) {
            return $caster->cast($var, $id);
        }

        $properties = (new ReflectionClass($var))->getProperties(
            ReflectionProperty::IS_STATIC |
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE,
        );

        $summary =
            $this->decorator->type($var::class) . ' ' .
            $this->decorator->comment("#{$id}");

        if (count($properties) === 0) {
            return $summary;
        }

        return $this->block(
            "{$summary} {",
            "}",
            $depth,
            function (int $depth) use ($var, $properties) {
                $string = '';
                foreach ($properties as $prop) {
                    $access = ($prop->getModifiers() & ReflectionProperty::IS_STATIC)
                        ? 'static '
                        : '';
                    $string .= $this->decorator->line(
                        $this->decorator->parameterKey($access . $prop->getName()) .
                        $this->decorator->parameterDelimiter(':') . ' ' .
                        $this->format($prop->getValue($var), $depth) .
                        $this->decorator->parameterDelimiter(','),
                        $depth,
                    );
                }
                return $string;
            },
        );
    }

    /**
     * @param array<mixed> $var
     * @param int $depth
     * @return string
     */
    protected function formatArray(array $var, int $depth): string
    {
        $start = $this->decorator->type('array(' . count($var) . ')') . ' [';
        $end = ']';

        if (count($var) === 0) {
            return "{$start}{$end}";
        }

        return $this->block(
            $start,
            $end,
            $depth,
            function(int $depth) use ($var) {
                $string = '';
                foreach ($var as $key => $val) {
                    $formattedKey = $this->decorator->parameterKey($key);
                    $formattedVal = $this->format($val, $depth);
                    $arrow = $this->decorator->parameterDelimiter('=>');
                    $string .= $this->decorator->line("{$formattedKey} {$arrow} {$formattedVal},", $depth);
                }
                return $string;
            },
        );
    }

    /**
     * @param resource $var
     * @param int $depth
     * @return string
     */
    protected function formatResource(mixed $var, int $depth): string
    {
        $type = $this->decorator->type(get_resource_type($var));
        $id = $this->decorator->comment('@' . get_resource_id($var));

        return $this->block(
            "{$type} {$id} {",
            "}",
            $depth,
            function(int $depth) use ($var) {
                $string = '';
                foreach (stream_get_meta_data($var) as $key => $val) {
                    $formattedKey = $this->decorator->parameterKey($key);
                    $formattedVal = $this->format($val, $depth);
                    $arrow = $this->decorator->parameterDelimiter(':');
                    $string .= $this->decorator->line("{$formattedKey}{$arrow} {$formattedVal},", $depth);
                }
                return $string;
            },
        );
    }

    /**
     * @param string $start
     * @param string $end
     * @param int $depth
     * @param Closure(int): string $block
     * @return string
     */
    public function block(string $start, string $end, int $depth, Closure $block): string
    {
        $string = ($depth === 0)
            ? $this->decorator->line($start, $depth)
            : $start . $this->decorator->eol();

        ++$depth;
        $string .= $block($depth);
        --$depth;

        $string .= $this->decorator->indent($end, $depth);

        if ($depth === 0) {
            $string .= $this->decorator->eol();
        }

        return $string;
    }

    /**
     * @param class-string $class
     * @param Closure(): Caster $callback
     * @return void
     */
    public function setCaster(string $class, Closure $callback): void
    {
        $this->casterResolvers[$class] = $callback;
    }

    /**
     * @param object $var
     * @return Caster|null
     */
    protected function getCaster(object $var): ?Caster
    {
        $class = $var::class;

        // Check if class already exists in resolved casters.
        if (array_key_exists($class, $this->resolvedCasters)) {
            return $this->resolvedCasters[$class];
        }

        // Check if class exists as resolver.
        if (array_key_exists($class, $this->casterResolvers)) {
            return $this->resolvedCasters[$class] ??= ($this->casterResolvers[$class])();
        }

        // Even if the class doesn't exist, check through all resolvers
        // and see if it inherits any registered classes.
        foreach ($this->casterResolvers as $_class => $resolver) {
            if (is_a($var, $_class)) {
                return $this->resolvedCasters[$_class] = $resolver();
            }
        }

        // If no match is found set it to null and let it run the default.
        return $this->resolvedCasters[$class] = null;
    }
}