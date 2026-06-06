# Switon Sharding Package

[![Sharding CI](https://img.shields.io/github/actions/workflow/status/switon-php/sharding/ci.yml?branch=main&label=Sharding%20CI)](https://github.com/switon-php/sharding/actions/workflows/ci.yml) [![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4)](https://www.php.net/)

Switon's shard resolution layer for strategy expressions, single-shard enforcement, and context-based routing across
connections and tables.

## Highlights

- **Shard resolution:** `unique()`, `multiple()`, and `all()` cover the common lookup modes.
- **Single-shard safety:** requests that resolve to too many shards are rejected.
- **Flexible context:** array, object, and empty contexts can all be handled.
- **Built-in strategies:** `modulo`, `list`, `range`, `crc32`, and `hash` are included.
- **Compact expressions:** short routing expressions can resolve into shard names.

## Installation

```bash
composer require switon/sharding
```

## Quick Start

```php
use Switon\Core\Attribute\Autowired;
use Switon\Sharding\ShardingManagerInterface;

class UserRepository
{
    #[Autowired] protected ShardingManagerInterface $shardingManager;

    public function find(int $userId): array
    {
        return $this->shardingManager->unique(
            'db:user_id%4',
            'user:user_id%8',
            ['user_id' => $userId],
        );
    }
}
```

Docs: https://docs.switon.dev/latest/sharding

## License

MIT.
