<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

use Switon\Core\Exception\MisuseException;

use function gettype;
use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;
use function preg_split;
use function trim;

/**
 * Routes numeric values by configured inclusive ranges.
 *
 * Use when shard boundaries are explicit intervals such as ID or time ranges.
 *
 * @see \Switon\Sharding\Strategy\AbstractStrategy
 * @see \Switon\Sharding\StrategyInterface
 */
class RangeStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function calculate(array $context, string $base, string $field, string $config): array
    {
        if ($field === '') {
            MisuseException::raise('Sharding field must not be empty');
        }

        // Parse ranges: "0-1000,1001-2000,2001-3000"
        $ranges = preg_split('#[\s,]+#', $config, -1, PREG_SPLIT_NO_EMPTY);
        $parsedRanges = [];

        foreach ($ranges ?: [] as $range) {
            if (preg_match('#^(\d+)-(\d+)$#', trim($range), $match)) {
                $parsedRanges[] = [(int)$match[1], (int)$match[2]];
            } else {
                MisuseException::raise('Invalid range format "{range}", expected "min-max"', ['range' => $range]);
            }
        }

        if (empty($parsedRanges)) {
            MisuseException::raise('No valid ranges found in config: "{config}"', ['config' => $config]);
        }

        // Get value from context
        $value = $context[$field] ?? null;

        // If no value in context (or null), return all shards
        if ($value === null) {
            $result = [];
            foreach ($parsedRanges as $index => $_) {
                $result[] = $this->formatShardName($base, $index);
            }
            return $result;
        }
        if (is_array($value) && in_array(null, $value, true)) {
            $result = [];
            foreach ($parsedRanges as $index => $_) {
                $result[] = $this->formatShardName($base, $index);
            }
            return $result;
        }

        $values = is_array($value) ? $value : [$value];
        $seen = [];
        $shards = [];

        foreach ($values as $val) {
            if (!is_numeric($val)) {
                MisuseException::raise('Field {field} requires numeric value, got {type}', ['field' => $field, 'type' => gettype($val)]);
            }

            $val = (int)$val;
            foreach ($parsedRanges as $index => [$min, $max]) {
                if ($val >= $min && $val <= $max) {
                    $shardName = $this->formatShardName($base, $index);
                    if (!isset($seen[$shardName])) {
                        $seen[$shardName] = true;
                        $shards[] = $shardName;
                    }
                }
            }
        }

        // If values don't match any range, return empty (no shards)
        return $shards;
    }
}
