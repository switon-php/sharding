<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

use Switon\Core\Exception\MisuseException;
use Switon\Sharding\StrategyInterface;

use function in_array;
use function is_array;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Base helper for concrete sharding strategies.
 *
 * Use when implementing common shard-name formatting and modulo-style routing behavior.
 *
 * Road-signs:
 * - formatShardName() for <code>{base}_{suffix}</code>
 * - calculateModuloLike() for modulo/hash-like strategies
 *
 * @see \Switon\Sharding\StrategyInterface
 * @see \Switon\Sharding\ShardingManager
 */
abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * Formats one shard name as <code>{base}_{suffix}</code>.
     */
    protected function formatShardName(string $base, string|int $suffix): string
    {
        return "{$base}_$suffix";
    }

    /**
     * Shared modulo-style sharding logic used by modulo/hash-like strategies.
     *
     * @param array<string, mixed> $context Sharding context.
     * @param string $base Base name.
     * @param string $field Sharding field.
     * @param string $config Divisor config.
     * @param callable $toInt Value normalizer to integer.
     *
     * @return array<int, string>
     */
    protected function calculateModuloLike(array $context, string $base, string $field, string $config, callable $toInt): array
    {
        if ($field === '') {
            MisuseException::raise('Sharding field must not be empty');
        }
        // Parse divisor and format
        $divisor = (int)$config;
        if ($divisor <= 0) {
            MisuseException::raise('Sharding divisor must be positive, got {divisor}', ['divisor' => $config]);
        }

        // Determine format (zero-padded or not)
        $format = '%d';
        $actualDivisor = $divisor;
        if (str_starts_with($config, '0')) {
            $actualDivisor = (int)substr($config, 1);
            $format = '%0' . strlen($config) . 'd';
        }

        if ($actualDivisor === 1) {
            return [$base];
        }

        // Get value from context
        $value = $context[$field] ?? null;

        // If no value in context (or null), return all shards
        if ($value === null) {
            $result = [];
            for ($i = 0; $i < $actualDivisor; $i++) {
                $result[] = $this->formatShardName($base, sprintf($format, $i));
            }
            return $result;
        }
        if (is_array($value) && in_array(null, $value, true)) {
            $result = [];
            for ($i = 0; $i < $actualDivisor; $i++) {
                $result[] = $this->formatShardName($base, sprintf($format, $i));
            }
            return $result;
        }

        // Calculate shards based on modulo
        $values = is_array($value) ? $value : [$value];
        $seen = [];
        $result = [];

        foreach ($values as $val) {
            $remainder = $toInt($val) % $actualDivisor;
            $shardName = $this->formatShardName($base, sprintf($format, $remainder));

            if (!isset($seen[$shardName])) {
                $seen[$shardName] = true;
                $result[] = $shardName;
            }
        }

        return $result;
    }
}
