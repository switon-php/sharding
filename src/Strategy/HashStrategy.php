<?php

declare(strict_types=1);

namespace Switon\Sharding\Strategy;

use Switon\Core\Attribute\Autowired;
use Switon\Core\Exception\MisuseException;
use Switon\Core\Json;

use function array_map;
use function count;
use function explode;
use function hash;
use function hash_algos;
use function in_array;
use function is_scalar;
use function substr;
use function unpack;

/**
 * Routes values by configurable hash algorithm and modulo divisor.
 *
 * Use when you need hash-based sharding with an algorithm other than plain CRC32.
 *
 * @see \Switon\Sharding\Strategy\AbstractStrategy
 * @see \Switon\Sharding\StrategyInterface
 */
class HashStrategy extends AbstractStrategy
{
    /** Default hash algorithm when config omits one. */
    #[Autowired] protected string $algo = 'crc32';

    /** Supported hash algorithms from <code>hash_algos()</code>. */
    /** @var list<string> */
    protected array $algos = [];

    /**
     * Loads supported hash algorithms once.
     */
    public function __construct()
    {
        $this->algos = hash_algos();
    }

    /**
     * {@inheritDoc}
     */
    public function calculate(array $context, string $base, string $field, string $config): array
    {
        [$algo, $divisorConfig] = $this->parseConfig($config);
        return $this->calculateModuloLike(
            $context,
            $base,
            $field,
            $divisorConfig,
            fn ($val) => $this->normalizeValue($val, $algo)
        );
    }

    /**
     * Parses hash config as <code>algo,N</code> or <code>N</code>.
     *
     * @return array{0: string, 1: string}
     */
    protected function parseConfig(string $config): array
    {
        $parts = array_map('trim', explode(',', $config));
        if (count($parts) === 1) {
            if ($parts[0] === '') {
                MisuseException::raise('Invalid hash config "{config}", expected "algo,N" or "N"', ['config' => $config]);
            }
            return [$this->algo, $parts[0]];
        }
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            MisuseException::raise('Invalid hash config "{config}", expected "algo,N" or "N"', ['config' => $config]);
        }

        $algo = $parts[0];
        return [$algo, $parts[1]];
    }

    /**
     * Converts input value to uint32 using the selected hash algorithm.
     */
    protected function normalizeValue(mixed $val, string $algo): int
    {
        $valueStr = is_scalar($val) ? (string)$val : Json::stringify($val);
        $supported = $this->algos;
        if (!in_array($algo, $supported, true)) {
            MisuseException::raise('Unsupported hash algorithm {algo}. Available: {algorithms}', [
                'algo' => $algo,
                'algorithms' => implode(', ', $supported),
            ]);
        }
        $raw = hash($algo, $valueStr, true);
        $bytes = substr($raw, 0, 4);
        $unpacked = unpack('N', $bytes);
        return (int)$unpacked[1];
    }
}
