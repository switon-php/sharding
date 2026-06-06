<?php

declare(strict_types=1);

namespace Switon\Sharding;

use Switon\Core\Exception\RuntimeException;

/**
 * Base exception for the Sharding component.
 *
 * Use when sharding-specific failures need one package-level exception root.
 *
 * @see \Switon\Sharding\ShardingManager
 * @see \Switon\Sharding\ShardingManagerInterface
 * @see \Switon\Core\Exception\RuntimeException
 */
class Exception extends RuntimeException
{
}
