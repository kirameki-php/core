<?php declare(strict_types=1);

namespace Kirameki\Core;

use JsonException as PhpJsonException;
use Kirameki\Core\Exceptions\JsonException;
use function json_decode;
use function json_encode;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Json extends StaticClass
{
    /**
     * Encode data as JSON string.
     *
     * Example:
     * ```php
     * Json::encode(true); // 'true'
     * Json::encode(['a' => 1]); // '{"a":1}'
     * Json::encode(['a' => 1], true); // "{\n    "a": 1\n}"
     * ```
     *
     * @param mixed $data
     * The data being encoded. String data must be UTF-8 encoded.
     * @param bool $formatted
     * [Optional] Format JSON in a human-readable format. Defaults to **false**.
     * @return string
     * JSON encoded string.
     */
    public static function encode(mixed $data, bool $formatted = false): string
    {
        $flags = JSON_PRESERVE_ZERO_FRACTION
               | JSON_UNESCAPED_UNICODE
               | JSON_UNESCAPED_SLASHES
               | JSON_THROW_ON_ERROR;

        if ($formatted) {
            $flags |= JSON_PRETTY_PRINT;
        }

        try {
            return json_encode($data, $flags);
        } catch (PhpJsonException $e) {
            throw new JsonException($e->getMessage(), [
                'type' => __FUNCTION__,
                'data' => $data,
                'options' => $flags,
                'formatted' => $formatted,
            ], $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Decodes a JSON string.
     *
     * Example:
     * ```php
     * Json::decode('true'); // true
     * Json::decode('{"a":1}'); // ['a' => 1]
     * ```
     *
     * @param string $json
     * The value being decoded. Must be a valid UTF-8 encoded string.
     * @return mixed
     * Decoded data.
     */
    public static function decode(string $json): mixed
    {
        try {
            $flags = JSON_THROW_ON_ERROR;

            return json_decode($json, flags: $flags);
        } catch (PhpJsonException $e) {
            throw new JsonException($e->getMessage(), [
                'type' => __FUNCTION__,
                'json' => $json,
            ], $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Validate a JSON string.
     *
     * Example:
     * ```php
     * Json::validate('true'); // true
     * Json::validate('[]'); // true
     * Json::validate('{"a":1}'); // true
     * Json::validate('{'); // false
     * ```
     *
     * @param string $json
     * The value being validated. Must be a valid UTF-8 encoded string.
     * @return bool
     * **true** if valid JSON, **false** otherwise.
     */
    public static function validate(string $json): bool
    {
        try {
            json_decode($json, flags: JSON_THROW_ON_ERROR);
        }
        catch (PhpJsonException) {
            return false;
        }
        return true;
    }
}
