<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use Switon\Core\Exception\MisuseException;
use Switon\Core\Json;
use Switon\Sharding\Strategy\HashStrategy;

use function hash;
use function is_scalar;
use function sprintf;
use function substr;
use function unpack;

class HashStrategyTest extends TestCase
{
    protected HashStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new HashStrategy();
    }

    public function testCalculateWithStringValue(): void
    {
        $context = ['user_id' => 'alice'];
        $result = $this->strategy->calculate($context, 'user', 'user_id', 'md5,8');

        $expected = [$this->expectedShard('md5', 'alice', 8)];
        $this->assertSame($expected, $result);
    }


    public function testCalculateWithInValuesDedupesOrderPreserving(): void
    {
        $values = ['a', 'b', 'a', 'c'];
        $result = $this->strategy->calculate(['user_id' => $values], 'user', 'user_id', 'md5,4');

        $expected = [];
        foreach ($values as $val) {
            $expected[] = $this->expectedShard('md5', $val, 4);
        }
        $expected = array_values(array_unique($expected));

        $this->assertSame($expected, $result);
    }

    public function testCalculateWithNullValueReturnsAllShards(): void
    {
        $result = $this->strategy->calculate([], 'user', 'user_id', 'md5,4');
        $this->assertSame(['user_0', 'user_1', 'user_2', 'user_3'], $result);
    }

    public function testCalculateWithEmptyInValuesReturnsEmpty(): void
    {
        $result = $this->strategy->calculate(['user_id' => []], 'user', 'user_id', 'md5,4');
        $this->assertSame([], $result);
    }

    public function testCalculateWithZeroPaddingConfig(): void
    {
        $result = $this->strategy->calculate(['user_id' => 'bob'], 'user', 'user_id', 'md5,08');
        $expected = ['user_' . sprintf('%02d', $this->expectedIndex('md5', 'bob', 8))];

        $this->assertSame($expected, $result);
    }

    public function testCalculateWithDefaultAlgoShorthand(): void
    {
        $result = $this->strategy->calculate(['user_id' => 'alice'], 'user', 'user_id', '8');
        $expected = [$this->expectedShard('crc32', 'alice', 8)];

        $this->assertSame($expected, $result);
    }

    public function testCalculateWithDefaultAlgoZeroPaddingShorthand(): void
    {
        $result = $this->strategy->calculate(['user_id' => 'bob'], 'user', 'user_id', '08');
        $expected = ['user_' . sprintf('%02d', $this->expectedIndex('crc32', 'bob', 8))];

        $this->assertSame($expected, $result);
    }

    public function testCalculateThrowsForEmptyConfig(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Invalid hash config');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', '');
    }

    public function testCalculateThrowsForMissingShardCount(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Invalid hash config');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', 'md5,');
    }

    public function testCalculateThrowsForEmptyAlgoAndShardCount(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Invalid hash config');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', ',');
    }

    public function testCalculateThrowsForUnsupportedAlgo(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Unsupported hash algorithm nope');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', 'nope,8');
    }

    public function testCalculateThrowsForInvalidDivisor(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding divisor must be positive');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', 'md5,0');
    }

    public function testCalculateThrowsForExtraConfigParts(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Invalid hash config');

        $this->strategy->calculate(['user_id' => 'x'], 'user', 'user_id', 'md5,8,extra');
    }

    private function expectedShard(string $algo, mixed $val, int $mod): string
    {
        return 'user_' . $this->expectedIndex($algo, $val, $mod);
    }

    private function expectedIndex(string $algo, mixed $val, int $mod): int
    {
        $valueStr = is_scalar($val) ? (string)$val : Json::stringify($val);
        $raw = hash($algo, $valueStr, true);
        $bytes = substr($raw, 0, 4);
        $unpacked = unpack('N', $bytes);
        return (int)$unpacked[1] % $mod;
    }
}
