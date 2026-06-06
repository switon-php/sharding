<?php

declare(strict_types=1);

namespace Switon\Sharding;

use Switon\Core\Attribute\Autowired;
use Switon\Sharding\Exception\InvalidShardingStrategyException;
use Switon\Sharding\Exception\ShardingTooManyException;

use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function count;
use function current;
use function explode;
use function is_array;
use function preg_match;
use function str_contains;
use function substr_count;

/**
 * Resolves shards from strategy expressions and runtime context values.
 *
 * Use when routing operations to one shard or to all matching shards.
 *
 * @see \Switon\Sharding\ShardingManagerInterface
 * @see \Switon\Sharding\StrategyInterface
 * @see \Switon\Sharding\Event\ShardValidationFailed
 * @see \Switon\Sharding\Exception
 * @see \Switon\Sharding\Exception\InvalidShardingStrategyException
 * @see \Switon\Sharding\Exception\ShardingTooManyException
 * @see \Switon\Sharding\Exception\ShardingNotSupportedException
 */
class ShardingManager implements ShardingManagerInterface
{
    /** @var array<string, StrategyInterface> */
    #[Autowired(instances: true)] protected array $strategies = [
        'modulo' => Strategy\ModuloStrategy::class,
        'list' => Strategy\ListStrategy::class,
        'range' => Strategy\RangeStrategy::class,
        'crc32' => Strategy\Crc32Strategy::class,
        'hash' => Strategy\HashStrategy::class,
    ];

    /**
     * {@inheritDoc}
     *
     * @throws \Switon\Sharding\Exception\ShardingTooManyException
     */
    public function unique(string $connection, string $table, mixed $context): array
    {
        if ($context === []) {
            ShardingTooManyException::raise('Cannot resolve unique shard for {table}: context array is empty, provide sharding key values', ['table' => $table]);
        }

        // Treat as a list of contexts when it's a numeric list (has index 0).
        // This is intentional for bulk operations: [$ctx1, $ctx2, ...] must resolve to the same shard.
        // This intentionally covers the single-element list case: [$context0].
        if (is_array($context) && array_key_exists(0, $context)) {
            $first = $context[0];

            [$c, $t] = $this->unique($connection, $table, $first);
            foreach ($context as $item) {
                [$cc, $tt] = $this->unique($connection, $table, $item);
                if ($cc !== $c || $tt !== $t) {
                    ShardingTooManyException::raise(
                        'Operation requires single shard but resolved to multiple: {shard1} and {shard2}',
                        ['shard1' => $c . '.' . $t, 'shard2' => $cc . '.' . $tt]
                    );
                }
            }

            return [$c, $t];
        }

        $shards = $this->multiple($connection, $table, $context);
        if (count($shards) !== 1) {
            ShardingTooManyException::raise('Operation spans {count} databases ({databases}), single database required for this operation', ['count' => count($shards), 'databases' => implode(', ', array_keys($shards))]);
        }

        $tables = current($shards);
        if (!is_array($tables) || count($tables) !== 1) {
            ShardingTooManyException::raise(
                'Operation requires single table but resolved to multiple: {tables}',
                ['tables' => is_array($tables) ? implode(', ', $tables) : (string)$tables]
            );
        }

        return [key($shards), $tables[0]];
    }


    /**
     * Parses one strategy expression into normalized parts.
     *
     * Supported formats:
     * - <code>base</code>
     * - <code>base:field%N</code>
     * - <code>base:list_items</code>
     * - <code>base:field:strategy:config</code>
     *
     * @param string $strategy Strategy expression.
     *
     * @return array{base: string, field: string, strategyId: ?string, config: ?string}
     */
    protected function parseStrategy(string $strategy): array
    {
        // No colon: treat as base name only (no sharding)
        if (!str_contains($strategy, ':')) {
            return ['base' => $strategy, 'field' => '', 'strategyId' => null, 'config' => null];
        }

        // Split by first colon: base and tail
        $parts = explode(':', $strategy, 2);
        [$base, $tail] = $parts;

        if ($base === '') {
            InvalidShardingStrategyException::raise('Invalid sharding strategy "{strategy}": base table/connection name is empty', ['strategy' => $strategy]);
        }
        if ($tail === '') {
            InvalidShardingStrategyException::raise('Invalid sharding strategy "{strategy}": configuration after colon is empty', ['strategy' => $strategy]);
        }

        // Count total colons to determine format
        $colonCount = substr_count($strategy, ':');

        if ($colonCount === 1) {
            // Only 1 colon: base:field%8 or base:0,1,2 (simplified formats)
            if (str_contains($tail, '%')) {
                // Modulo format: field%8 (field must be valid PHP identifier)
                if (preg_match('#^([a-zA-Z_]\w*)%(\d+)$#', $tail, $match)) {
                    return ['base' => $base, 'field' => $match[1], 'strategyId' => 'modulo', 'config' => $match[2]];
                }
                InvalidShardingStrategyException::raise('Invalid modulo sharding format "{strategy}": expected "base:field%N" (e.g., "users:user_id%8")', ['strategy' => $strategy]);
            }
            // List format: base:0,1,2
            return ['base' => $base, 'field' => '', 'strategyId' => 'list', 'config' => $tail];
        }

        if ($colonCount === 3) {
            // 3 colons: base:field:strategy:config (standard format)
            $parts = explode(':', $tail, 3);
            [$field, $strategyId, $config] = $parts;
            if ($strategyId === '') {
                InvalidShardingStrategyException::raise('Invalid sharding strategy "{strategy}": strategy ID is empty (expected modulo, list, range, crc32, or hash)', ['strategy' => $strategy]);
            }
            if ($config === '') {
                InvalidShardingStrategyException::raise('Invalid sharding strategy "{strategy}": config section is empty', ['strategy' => $strategy]);
            }
            // Note: field can be empty for list strategy, but other strategies validate in calculate()
            return ['base' => $base, 'field' => $field, 'strategyId' => $strategyId, 'config' => $config];
        }

        InvalidShardingStrategyException::raise('Invalid sharding strategy format "{strategy}": expected 1 or 3 colons (e.g., "base:field%8" or "base:field:strategy:config")', ['strategy' => $strategy]);
    }

    /**
     * Resolves shard names from one strategy expression and context.
     *
     * @param string $strategy Strategy expression.
     * @param array<string, mixed> $context Sharding context.
     *
     * @return array<int, string>
     *
     * @throws \Switon\Sharding\Exception\InvalidShardingStrategyException
     */
    protected function explode(string $strategy, array $context = []): array
    {
        $parsed = $this->parseStrategy($strategy);
        if ($parsed['strategyId'] === null) {
            return [$parsed['base']];
        }

        // Get strategy from instances
        $strategyInstance = $this->strategies[$parsed['strategyId']] ?? null;
        if (!$strategyInstance instanceof StrategyInterface) {
            InvalidShardingStrategyException::raise('Unknown sharding strategy "{strategy}", available: {available}', ['strategy' => $parsed['strategyId'], 'available' => implode(', ', array_keys($this->strategies))]);
        }

        return $strategyInstance->calculate($context, $parsed['base'], $parsed['field'], (string)$parsed['config']);
    }


    /**
     * {@inheritDoc}
     */
    public function all(string $connection, string $table): array
    {
        $dbs = $this->explode($connection);
        $tables = $this->explode($table);

        return array_fill_keys($dbs, $tables);
    }

    /**
     * {@inheritDoc}
     */
    public function multiple(string $connection, string $table, mixed $context): array
    {
        if ($context === null || $context === []) {
            return $this->all($connection, $table);
        }

        // Convert Entity object to array for sharding calculation
        if (is_object($context)) {
            $context = get_object_vars($context);
        }

        // Use explode() for both db and table, which delegates to strategies
        $dbs = $this->explode($connection, $context);
        $tables = $this->explode($table, $context);

        return $tables ? array_fill_keys($dbs, $tables) : [];
    }
}
