<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit;

use Switon\Sharding\Tests\TestCase;

class ManagerBasicTest extends TestCase
{
    public function testAllWithNonShardedNames(): void
    {
        $connection = 'db1';
        $table = 'users';

        $result = $this->shardingManager->all($connection, $table);

        $expected = ['db1' => ['users']];
        $this->assertSame($expected, $result);
    }

    public function testAllWithShardedConnection(): void
    {
        $connection = 'db:user_id%8';
        $table = 'users';

        $result = $this->shardingManager->all($connection, $table);

        $this->assertCount(8, $result);
        foreach ($result as $conn => $tables) {
            $this->assertSame(['users'], $tables);
        }
    }

    public function testAllWithShardedTable(): void
    {
        $connection = 'db1';
        $table = 'users:user_id%8';

        $result = $this->shardingManager->all($connection, $table);

        $this->assertArrayHasKey('db1', $result);
        $this->assertCount(8, $result['db1']);
    }

    public function testAllWithListStrategy(): void
    {
        $connection = 'db1';
        $table = 'users:0,1,2';

        $result = $this->shardingManager->all($connection, $table);

        $expected = ['db1' => ['users_0', 'users_1', 'users_2']];
        $this->assertSame($expected, $result);
    }

    public function testMultipleWithEmptyContextReturnsAll(): void
    {
        $connection = 'users:user_id%8';
        $table = 'users';
        $context = [];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $this->assertCount(8, $result);
    }

    public function testMultipleWithNullContextReturnsAll(): void
    {
        $connection = 'users:user_id%8';
        $table = 'users';
        $context = null;

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $this->assertCount(8, $result);
    }

    public function testMultipleWithContextReturnsSpecificShard(): void
    {
        $connection = 'db1';
        $table = 'users:user_id%8';
        $context = ['user_id' => 5];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $expected = ['db1' => ['users_5']];
        $this->assertSame($expected, $result);
    }

    public function testMultipleWithNonShardedNames(): void
    {
        $connection = 'db1';
        $table = 'users';
        $context = ['user_id' => 5];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $expected = ['db1' => ['users']];
        $this->assertSame($expected, $result);
    }

    public function testMultipleWithShardedConnectionAndTable(): void
    {
        $connection = 'db:user_id%4';
        $table = 'users:user_id%8';
        $context = ['user_id' => 5];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $this->assertArrayHasKey('db_1', $result);
        $this->assertSame(['users_5'], $result['db_1']);
    }

    public function testMultipleWithArrayContextValues(): void
    {
        $connection = 'db1';
        $table = 'users:user_id%8';
        $context = ['user_id' => [5, 6, 7]];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $this->assertArrayHasKey('db1', $result);
        $this->assertCount(3, $result['db1']);
        $this->assertContains('users_5', $result['db1']);
        $this->assertContains('users_6', $result['db1']);
        $this->assertContains('users_7', $result['db1']);
    }

    public function testMultipleWithObjectContext(): void
    {
        $connection = 'db1';
        $table = 'users:user_id%8';
        $context = (object)['user_id' => 5];

        $result = $this->shardingManager->multiple($connection, $table, $context);

        $expected = ['db1' => ['users_5']];
        $this->assertSame($expected, $result);
    }
}
