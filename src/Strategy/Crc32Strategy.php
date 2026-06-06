<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

use Switon\Core\Json;

use function crc32;
use function is_scalar;

/**
 * Routes values by <code>crc32(value) % N</code>.
 *
 * Use when sharding keys are strings such as UUIDs or user names.
 *
 * @see \Switon\Sharding\Strategy\ModuloStrategy
 * @see \Switon\Sharding\StrategyInterface
 */
class Crc32Strategy extends ModuloStrategy
{
    /**
     * Converts input value to an unsigned CRC32 integer.
     */
    protected function normalizeValue(mixed $val): int
    {
        $valueStr = is_scalar($val) ? (string)$val : Json::stringify($val);

        $hash = crc32($valueStr);
        $hash &= 0xFFFFFFFF;

        return $hash;
    }
}
