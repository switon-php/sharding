<?php

declare(strict_types=1);

namespace Switon\Sharding\Tests\Unit;

use Switon\Core\Exception\MisuseException;
use Switon\Sharding\Exception\InvalidShardingStrategyException;
use Switon\Sharding\Tests\TestCase;

class ManagerExplodeTest extends TestCase
{
    public function testExplodeWithNonShardedName(): void
    {
        $result = $this->shardingManager->all('users', 'table');

        $this->assertArrayHasKey('users', $result);
        $this->assertSame(['table'], $result['users']);
    }

    public function testExplodeWithModuloShorthand(): void
    {
        $result = $this->shardingManager->multiple('db1', 'users:user_id%8', ['user_id' => 5]);

        $this->assertArrayHasKey('db1', $result);
        $this->assertSame(['users_5'], $result['db1']);
    }

    public function testExplodeWithModuloStandardFormat(): void
    {
        $result = $this->shardingManager->multiple('db1', 'users:user_id:modulo:8', ['user_id' => 5]);

        $this->assertArrayHasKey('db1', $result);
        $this->assertSame(['users_5'], $result['db1']);
    }

    public function testExplodeWithListFormat(): void
    {
        $result = $this->shardingManager->all('db1', 'users:0,1,2');

        $this->assertArrayHasKey('db1', $result);
        $expected = ['users_0', 'users_1', 'users_2'];
        $this->assertSame($expected, $result['db1']);
    }

    public function testExplodeWithListStandardFormat(): void
    {
        $result = $this->shardingManager->all('db1', 'users:user_id:list:0,1,2');

        $this->assertArrayHasKey('db1', $result);
        $expected = ['users_0', 'users_1', 'users_2'];
        $this->assertSame($expected, $result['db1']);
    }

    public function testExplodeWithRangeFormat(): void
    {
        $result = $this->shardingManager->multiple('db1', 'users:user_id:range:0-1000,1001-2000', ['user_id' => 500]);

        $this->assertArrayHasKey('db1', $result);
        $this->assertSame(['users_0'], $result['db1']);
    }

    public function testExplodeWithCrc32Format(): void
    {
        $result = $this->shardingManager->multiple('db1', 'users:user_id:crc32:8', ['user_id' => 'test']);

        $this->assertArrayHasKey('db1', $result);
        $this->assertCount(1, $result['db1']);
        $this->assertStringStartsWith('users_', $result['db1'][0]);
    }

    public function testExplodeThrowsExceptionForInvalidModuloFormat(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/Invalid modulo sharding format/');

        $this->shardingManager->all('db1', 'users:user_id%invalid');
    }

    public function testExplodeThrowsExceptionForInvalidFormatTwoColons(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/Invalid sharding strategy format/');

        $this->shardingManager->all('db1', 'users:user_id:invalid');
    }

    public function testExplodeThrowsExceptionForInvalidFormatTooManyColons(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/Invalid sharding strategy format/');

        $this->shardingManager->all('db1', 'users:user_id:modulo:8:extra');
    }

    public function testExplodeThrowsExceptionForUnknownStrategy(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/Unknown sharding strategy/');

        $this->shardingManager->all('db1', 'users:user_id:unknown:config');
    }

    public function testExplodeThrowsExceptionForEmptyBase(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/base table\/connection name is empty/');

        $this->shardingManager->all('db1', ':user_id%8');
    }

    public function testExplodeThrowsExceptionForEmptyConfigPart(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/configuration after colon is empty/');

        $this->shardingManager->all('db1', 'users:');
    }

    public function testExplodeThrowsExceptionForEmptyFieldInStandardFormat(): void
    {
        $this->expectException(MisuseException::class);
        $this->expectExceptionMessage('Sharding field must not be empty');

        $this->shardingManager->all('db1', 'users::modulo:8');
    }

    public function testExplodeThrowsExceptionForEmptyStrategyIdInStandardFormat(): void
    {
        $this->expectException(InvalidShardingStrategyException::class);
        $this->expectExceptionMessageMatches('/strategy ID is empty/');

        $this->shardingManager->all('db1', 'users:user_id::8');
    }
}
