<?php

declare(strict_types=1);

namespace Switon\Sharding;

use Switon\Sharding\Exception\ShardingTooManyException;

/**
 * Defines shard resolution APIs for connection and table routing.
 *
 * Use when callers need one shard, all matching shards, or all configured shards
 * from the same strategy-expression contract.
 *
 * Road-signs:
 * - unique() for single-shard enforcement
 * - multiple() for context-based shard expansion
 * - all() for full configured shard lists
 * - StrategyInterface implementations
 *
 * @see \Switon\Sharding\ShardingManager
 * @see \Switon\Sharding\StrategyInterface
 * @see \Switon\Sharding\Exception
 * @see \Switon\Sharding\Exception\ShardingTooManyException
 * @see \Switon\Query\Query Typical consumer
 * @see \Switon\Orm\EntityManager Typical consumer
 */
interface ShardingManagerInterface
{
    /**
     * Resolves exactly one shard.
     *
     * Accepts one context (array/object) or a numeric list of contexts.
     * When a list is given, all items must resolve to the same shard.
     *
     * @param string $connection Connection name or strategy expression.
     * @param string $table Table name or strategy expression.
     * @param mixed $context Single context or context list.
     *
     * @return array{0: string, 1: string} [connection, table]
     *
     * @throws ShardingTooManyException
     */
    public function unique(string $connection, string $table, mixed $context): array;

    /**
     * Resolves all matched shards for the given context.
     *
     * @param string $connection Connection name or strategy expression.
     * @param string $table Table name or strategy expression.
     * @param mixed $context Sharding context.
     *
     * @return array<string, array<int, string>>
     *
     * @see \Switon\Sharding\ShardingManager::multiple() Default context normalization path
     */
    public function multiple(string $connection, string $table, mixed $context): array;

    /**
     * Resolves all configured shards without context filtering.
     *
     * @param string $connection Connection name or strategy expression.
     * @param string $table Table name or strategy expression.
     *
     * @return array<string, array<int, string>>
     */
    public function all(string $connection, string $table): array;
}
