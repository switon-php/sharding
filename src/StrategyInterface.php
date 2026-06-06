<?php

declare(strict_types=1);

namespace Switon\Sharding;

/**
 * Defines one sharding calculation strategy.
 *
 * Use when mapping context values to connection/table shard suffixes.
 *
 * Road-signs:
 * - calculate() returns physical shard names
 * - context field lookup is strategy-defined
 * - consumed by ShardingManager::explode()
 *
 * @see \Switon\Sharding\ShardingManager
 * @see \Switon\Sharding\Strategy\AbstractStrategy
 */
interface StrategyInterface
{
    /**
     * Calculates shard names for one strategy expression.
     *
     * @param array<string, mixed> $context Sharding context.
     * @param string $base Base name.
     * @param string $field Sharding field name.
     * @param string $config Strategy config segment.
     *
     * @return array<int, string>
     */
    public function calculate(array $context, string $base, string $field, string $config): array;
}
