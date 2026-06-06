<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit;

use Switon\Sharding\Exception\ShardingTooManyException;
use Switon\Sharding\ShardingManagerInterface;
use Switon\Sharding\Tests\TestCase;

class ManagerUniqueTest extends TestCase
{
    public function testUniqueNonShardedReturnsSameConnectionAndTable(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $result = $manager->unique('main', 'users', null);
        self::assertSame(['main', 'users'], $result);

        $result = $manager->unique('main', 'users', ['id' => 1]);
        self::assertSame(['main', 'users'], $result);
    }

    public function testUniqueShardedMissingContextThrows(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $this->expectException(ShardingTooManyException::class);
        $manager->unique('db:user_id%2', 'users', null);
    }

    public function testUniqueForManyContextsValidatesSameShard(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $result = $manager->unique('db:user_id%2', 'users', [
            ['user_id' => 1],
            ['user_id' => 3],
        ]);

        // 1%2=1, 3%2=1 -> db_1
        self::assertSame(['db_1', 'users'], $result);
    }

    public function testUniqueForSingleElementContextList(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $result = $manager->unique('db:user_id%2', 'users', [
            ['user_id' => 1],
        ]);

        self::assertSame(['db_1', 'users'], $result);
    }

    public function testUniqueForManyContextsMismatchThrows(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $this->expectException(ShardingTooManyException::class);
        $manager->unique('db:user_id%2', 'users', [
            ['user_id' => 1],
            ['user_id' => 2],
        ]);
    }

    public function testUniqueWithEmptyContextListThrows(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $this->expectException(ShardingTooManyException::class);
        $this->expectExceptionMessageMatches('/context array is empty/');

        $manager->unique('db:user_id%2', 'users', []);
    }

    public function testUniqueAcceptsObjectContextForShardedStrategy(): void
    {
        $manager = $this->container->get(ShardingManagerInterface::class);

        $result = $manager->unique('db:user_id%2', 'users', (object)['user_id' => 4]);

        self::assertSame(['db_0', 'users'], $result);
    }
}
