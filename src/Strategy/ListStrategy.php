<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

use Switon\Core\Exception\MisuseException;

use function preg_split;

/**
 * Returns a fixed shard list from configuration.
 *
 * Use when shard targets are predefined and independent from context values.
 *
 * @see \Switon\Sharding\Strategy\AbstractStrategy
 * @see \Switon\Sharding\StrategyInterface
 */
class ListStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     *
     * List strategy ignores <code>$context</code> and <code>$field</code>.
     */
    public function calculate(array $context, string $base, string $field, string $config): array
    {
        // Parse list items
        $items = preg_split('#[\s,]+#', $config, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($items)) {
            MisuseException::raise('Sharding list configuration is empty', ['config' => $config]);
        }

        $result = [];

        foreach ($items as $item) {
            $result[] = $this->formatShardName($base, $item);
        }

        return $result;
    }
}
