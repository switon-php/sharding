<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use Switon\Core\Exception\MisuseException;
use Switon\Sharding\Strategy\RangeStrategy;

class RangeStrategyTest extends TestCase
{
    protected RangeStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new RangeStrategy();
    }

    public function testCalculateWithValueInRange(): void
    {
        $context = ['user_id' => 500];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithZeroValue(): void
    {
        $context = ['user_id' => 0];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithValueInSecondRange(): void
    {
        $context = ['user_id' => 1500];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_1'], $result);
    }

    public function testCalculateWithValueAtBoundary(): void
    {
        $context = ['user_id' => 1000];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithValueOutsideRanges(): void
    {
        $context = ['user_id' => 5000];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame([], $result);
    }

    public function testCalculateReturnsAllShardsWhenValueIsNull(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_0', 'user_1', 'user_2'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateThrowsExceptionForInvalidRangeFormat(): void
    {
        $context = ['user_id' => 500];
        $base = 'user';
        $field = 'user_id';
        $config = 'invalid-range';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Invalid range format');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateWithInValuesContainingNullReturnsAllShards(): void
    {
        $context = ['user_id' => [500, null]];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0', 'user_1', 'user_2'], $result);
    }

    public function testCalculateWithEmptyInValuesReturnsEmpty(): void
    {
        $context = ['user_id' => []];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame([], $result);
    }

    public function testCalculateThrowsExceptionForEmptyConfig(): void
    {
        $context = ['user_id' => 500];
        $base = 'user';
        $field = 'user_id';
        $config = '';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('No valid ranges found in config');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateWithSpaceSeparatedRanges(): void
    {
        $context = ['user_id' => 500];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000 1001-2000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithMixedSeparators(): void
    {
        $context = ['user_id' => 1500];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000, 1001-2000 , 2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_1'], $result);
    }

    public function testCalculateWithInValues(): void
    {
        $context = ['user_id' => [500, 1500, 5000, 500]];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0', 'user_1'], $result);
    }

    public function testCalculateWithInValuesAllOutOfRange(): void
    {
        $context = ['user_id' => [5000, 6000]];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000,2001-3000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame([], $result);
    }

    public function testCalculateWithInValuesAtBoundaries(): void
    {
        $context = ['user_id' => [0, 1000, 1001, 2000]];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0', 'user_1'], $result);
    }

    public function testCalculateWithInValuesThrowsForNonNumeric(): void
    {
        $context = ['user_id' => [500, 'not-a-number']];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('requires numeric value, got');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateThrowsExceptionForNonNumericValue(): void
    {
        $context = ['user_id' => 'not-a-number'];
        $base = 'user';
        $field = 'user_id';
        $config = '0-1000,1001-2000';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('requires numeric value, got');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateThrowsExceptionForEmptyField(): void
    {
        $context = ['user_id' => 500];
        $base = 'user';
        $field = '';
        $config = '0-1000,1001-2000';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding field must not be empty');

        $this->strategy->calculate($context, $base, $field, $config);
    }
}
