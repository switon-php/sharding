<?php

declare(strict_types=1);

namespace Switon\Sharding\Exception;

use Switon\Sharding\Exception as BaseException;

/**
 * Exception for invalid sharding strategy expressions.
 *
 * Raised when strategy format, strategy ID, or strategy config cannot be parsed or resolved.
 * Fix: verify the expression shape such as <code>base:field%N</code> or <code>base:field:strategy:config</code>.
 *
 * @see \Switon\Sharding\Exception
 * @see \Switon\Sharding\ShardingManager
 */
class InvalidShardingStrategyException extends BaseException
{
}
