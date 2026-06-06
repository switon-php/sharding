<?php

declare(strict_types=1);

namespace Switon\Sharding\Exception;

use Switon\Sharding\Exception as BaseException;

/**
 * Exception for operations not supported in sharded execution paths.
 *
 * Raised when query behavior requires unsupported cross-shard semantics.
 * Fix: constrain the operation to one shard or switch to a supported execution path.
 *
 * @see \Switon\Sharding\Exception
 * @see \Switon\Sharding\ShardingManager
 */
class ShardingNotSupportedException extends BaseException
{
}
