<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

/**
 * Routes values with modulo arithmetic.
 *
 * Use when sharding key values are numeric and evenly distributed.
 *
 * @see \Switon\Sharding\Strategy\AbstractStrategy
 * @see \Switon\Sharding\StrategyInterface
 */
class ModuloStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function calculate(array $context, string $base, string $field, string $config): array
    {
        return $this->calculateModuloLike($context, $base, $field, $config, fn ($val) => $this->normalizeValue($val));
    }

    /**
     * Converts input value to integer before modulo routing.
     */
    protected function normalizeValue(mixed $val): int
    {
        return (int)$val;
    }
}
