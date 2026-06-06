<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use Switon\Core\Exception\MisuseException;
use Switon\Sharding\Strategy\Crc32Strategy;

class Crc32StrategyTest extends TestCase
{
    protected Crc32Strategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new Crc32Strategy();
    }

    public function testCalculateWithStringValue(): void
    {
        $context = ['user_id' => 'test123'];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('user_', $result[0]);
    }

    public function testCalculateWithEmptyStringValue(): void
    {
        $context = ['user_id' => ''];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('user_', $result[0]);
    }

    public function testCalculateWithInValuesHashesEachElement(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '4';
        $values = [10, 11, 12, 10];

        $result = $this->strategy->calculate(['user_id' => $values], $base, $field, $config);

        $expected = [];
        foreach ($values as $val) {
            $hash = crc32((string)$val);
            $hash &= 0xFFFFFFFF;
            $expected[] = "user_" . ($hash % (int)$config);
        }
        $expected = array_values(array_unique($expected));

        $this->assertSame($expected, $result);
    }

    public function testCalculateWithInValuesDedupesOrderPreserving(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '4';
        $values = ['a', 'b', 'a', 'c'];

        $result = $this->strategy->calculate(['user_id' => $values], $base, $field, $config);

        $expected = [];
        foreach ($values as $val) {
            $hash = crc32((string)$val) & 0xFFFFFFFF;
            $expected[] = 'user_' . ($hash % (int)$config);
        }
        $expected = array_values(array_unique($expected));

        $this->assertSame($expected, $result);
    }

    public function testCalculateWithInValuesContainingNullReturnsAllShards(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '4';
        $values = [10, null, 12];

        $result = $this->strategy->calculate(['user_id' => $values], $base, $field, $config);

        $this->assertSame(['user_0', 'user_1', 'user_2', 'user_3'], $result);
    }

    public function testCalculateWithEmptyInValuesReturnsEmpty(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '4';
        $values = [];

        $result = $this->strategy->calculate(['user_id' => $values], $base, $field, $config);

        $this->assertSame([], $result);
    }

    public function testCalculateWithNumericValue(): void
    {
        $context = ['user_id' => 12345];
        $base = 'user';
        $field = 'user_id';
        $config = '16';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('user_', $result[0]);
    }

    public function testCalculateIsDeterministic(): void
    {
        $context = ['user_id' => 'test123'];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result1 = $this->strategy->calculate($context, $base, $field, $config);
        $result2 = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame($result1, $result2);
    }

    public function testCalculateReturnsAllShardsWhenValueIsNull(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_0', 'user_1', 'user_2', 'user_3', 'user_4', 'user_5', 'user_6', 'user_7'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateThrowsExceptionForInvalidShardCount(): void
    {
        $context = ['user_id' => 'test'];
        $base = 'user';
        $field = 'user_id';
        $config = '0';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding divisor must be positive');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateWithArrayValue(): void
    {
        $context = ['user_id' => ['key' => 'value']];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertCount(1, $result);
        $this->assertStringStartsWith('user_', $result[0]);
    }

    public function testCalculateWithDifferentValues(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result1 = $this->strategy->calculate(['user_id' => 'value1'], $base, $field, $config);
        $result2 = $this->strategy->calculate(['user_id' => 'value2'], $base, $field, $config);

        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
        $this->assertStringStartsWith('user_', $result1[0]);
        $this->assertStringStartsWith('user_', $result2[0]);
    }

    public function testCalculateWithSingleShardReturnsBaseName(): void
    {
        $context = ['user_id' => 'test'];
        $base = 'user';
        $field = 'user_id';
        $config = '1';

        $result = $this->strategy->calculate($context, $base, $field, $config);
        $this->assertSame(['user'], $result);
    }

    public function testCalculateWithZeroPaddingConfig(): void
    {
        $context = ['user_id' => 3];
        $base = 'user';
        $field = 'user_id';
        $config = '08';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $hash = crc32('3') & 0xFFFFFFFF;
        $expected = ['user_' . sprintf('%02d', $hash % 8)];
        $this->assertSame($expected, $result);
    }

    public function testCalculateWithZeroPaddingConfigReturnsPaddedAllShardsWhenNull(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '08';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_00', 'user_01', 'user_02', 'user_03', 'user_04', 'user_05', 'user_06', 'user_07'], $result);
    }

    public function testCalculateWithZeroPaddingConfigForInValues(): void
    {
        $base = 'user';
        $field = 'user_id';
        $config = '08';
        $values = ['a', 'b', 'a'];

        $result = $this->strategy->calculate(['user_id' => $values], $base, $field, $config);

        $expected = [];
        foreach ($values as $val) {
            $hash = crc32((string)$val) & 0xFFFFFFFF;
            $expected[] = 'user_' . sprintf('%02d', $hash % 8);
        }
        $expected = array_values(array_unique($expected));

        $this->assertSame($expected, $result);
    }
}
