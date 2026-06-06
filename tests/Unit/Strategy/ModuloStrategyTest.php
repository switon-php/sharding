<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use Switon\Core\Exception\MisuseException;
use Switon\Sharding\Strategy\ModuloStrategy;

class ModuloStrategyTest extends TestCase
{
    protected ModuloStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ModuloStrategy();
    }

    public function testCalculateWithSingleValue(): void
    {
        $context = ['user_id' => 5];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_5'], $result);
    }

    public function testCalculateWithArrayValues(): void
    {
        $context = ['user_id' => [5, 13, 21]];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_5'], $result);
    }

    public function testCalculateWithMultipleDistinctShards(): void
    {
        $context = ['user_id' => [5, 6, 7]];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertCount(3, $result);
        $this->assertContains('user_5', $result);
        $this->assertContains('user_6', $result);
        $this->assertContains('user_7', $result);
    }

    public function testCalculateWithZeroPaddedFormat(): void
    {
        $context = ['user_id' => 5];
        $base = 'user';
        $field = 'user_id';
        $config = '08';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_05'], $result);
    }

    public function testCalculateWithZeroPaddedFormatForInValues(): void
    {
        $context = ['user_id' => [1, 2, 10]];
        $base = 'user';
        $field = 'user_id';
        $config = '08';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_01', 'user_02'], $result);
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

    public function testCalculateWithDivisorOfOne(): void
    {
        $context = ['user_id' => 5];
        $base = 'user';
        $field = 'user_id';
        $config = '1';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user'], $result);
    }

    public function testCalculateWithInValuesContainingNullReturnsAllShards(): void
    {
        $context = ['user_id' => [10, null, 12]];
        $base = 'user';
        $field = 'user_id';
        $config = '4';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0', 'user_1', 'user_2', 'user_3'], $result);
    }

    public function testCalculateWithEmptyInValuesReturnsEmpty(): void
    {
        $context = ['user_id' => []];
        $base = 'user';
        $field = 'user_id';
        $config = '4';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame([], $result);
    }

    public function testCalculateThrowsExceptionForInvalidDivisor(): void
    {
        $context = ['user_id' => 5];
        $base = 'user';
        $field = 'user_id';
        $config = '0';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding divisor must be positive');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateWithNegativeValue(): void
    {
        $context = ['user_id' => -5];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testCalculateWithZeroValue(): void
    {
        $context = ['user_id' => 0];
        $base = 'user';
        $field = 'user_id';
        $config = '8';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithArrayValuesDedupesOrderPreserving(): void
    {
        $context = ['user_id' => [10, 11, 10, 12]];
        $base = 'user';
        $field = 'user_id';
        $config = '4';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_2', 'user_3', 'user_0'], $result);
    }

    public function testCalculateWithZeroPaddedFormatReturnsPaddedAllShardsWhenNull(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '08';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_00', 'user_01', 'user_02', 'user_03', 'user_04', 'user_05', 'user_06', 'user_07'], $result);
    }
}
