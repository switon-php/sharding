<?php

declare(strict_types=1);

namespace Switon\Sharding\Exception;

use Switon\Sharding\Exception as BaseException;

/**
 * Exception for operations that require one shard but resolve to many.
 *
 * Raised when single-shard operations produce multiple databases or tables.
 * Fix: provide a narrower sharding context or use a multi-shard-capable operation.
 *
 * @see \Switon\Sharding\Exception
 * @see \Switon\Sharding\ShardingManager
 */
class ShardingTooManyException extends BaseException
{
}
