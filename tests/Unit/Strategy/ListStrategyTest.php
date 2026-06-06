<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit\Strategy;

use PHPUnit\Framework\TestCase;
use Switon\Core\Exception\MisuseException;
use Switon\Sharding\Strategy\ListStrategy;

class ListStrategyTest extends TestCase
{
    protected ListStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ListStrategy();
    }

    public function testCalculateReturnsAllShards(): void
    {
        $context = ['user_id' => 5];
        $base = 'user';
        $field = 'user_id';
        $config = '0,1,2';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_0', 'user_1', 'user_2'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateIgnoresContext(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = 'a,b,c';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_a', 'user_b', 'user_c'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateIgnoresContextWithInAndNull(): void
    {
        $context = ['user_id' => [1, null, 2]];
        $base = 'user';
        $field = 'user_id';
        $config = 'x,y';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_x', 'user_y'], $result);
    }

    public function testCalculateWithSpaceSeparatedValues(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '0 1 2';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_0', 'user_1', 'user_2'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateWithMixedSeparators(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '0, 1 , 2';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_0', 'user_1', 'user_2'];
        $this->assertSame($expected, $result);
    }

    public function testCalculateThrowsExceptionForEmptyConfig(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '';

        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding list configuration is empty');

        $this->strategy->calculate($context, $base, $field, $config);
    }

    public function testCalculateWithSingleItem(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = '0';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $this->assertSame(['user_0'], $result);
    }

    public function testCalculateWithNonNumericSuffixes(): void
    {
        $context = [];
        $base = 'user';
        $field = 'user_id';
        $config = 'shard1,shard2,shard3';

        $result = $this->strategy->calculate($context, $base, $field, $config);

        $expected = ['user_shard1', 'user_shard2', 'user_shard3'];
        $this->assertSame($expected, $result);
    }
}
