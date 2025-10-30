# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `laravel-clickhouse-eloquent`, a Laravel adapter that integrates ClickHouse database support into Laravel applications. It combines the phpClickHouse client (smi2/phpClickHouse) for connections with the Tinderbox ClickhouseBuilder for query building, providing an Eloquent-like interface for ClickHouse operations.

**Key distinction**: ClickHouse is an analytical database with limited UPDATE/DELETE capabilities. Most operations are append-only inserts with batch processing.

## Development Commands

### Running Tests
```bash
# Run all tests via Docker
docker-compose -f docker-compose.test.yaml run php sh /src/tests.bootstrap.sh

# Run specific test
docker-compose -f docker-compose.test.yaml run php sh -c "cd /app && php artisan test --filter testSimpleModelInsertAndSelect"
```

Tests run in a containerized Laravel environment with two ClickHouse server instances (for multi-connection testing).

### Local Development
```bash
# Install dependencies
composer install

# PHPUnit configuration is in phpunit.xml
# Tests are in tests/ directory
```

## Architecture

### Core Components

**Connection Layer** (`src/Connection.php`):
- Extends Laravel's `Illuminate\Database\Connection`
- Wraps the `ClickHouseDB\Client` from smi2/phpClickHouse
- Factory method: `Connection::createWithClient(array $config)`
- Handles connection configuration including timeouts, HTTPS, retries, and custom settings

**Query Builder** (`src/Builder.php`):
- Extends `Tinderbox\ClickhouseBuilder\Query\BaseBuilder`
- Adds ClickHouse-specific features: `settings()` clause for query-level settings
- Methods: `get()`, `getRows()`, `chunk()`, `delete()`, `update()`
- Uses `WithClient` trait to access the ClickHouse client

**BaseModel** (`src/BaseModel.php`):
- Eloquent-inspired model base class (NOT an actual Eloquent model)
- Uses Laravel traits: `HasAttributes`, `HidesAttributes`, `HasEvents`
- Static insert methods: `insertBulk()`, `insertAssoc()`, `prepareAndInsert()`
- Query methods: `select()`, `where()`, `whereRaw()`, `query()`
- ClickHouse-specific operations: `optimize()`, `truncate()`
- Supports events: `creating`, `created`, `saved`
- **Important**: `save()` throws exception on updates (ClickHouse limitation)

**Migration** (`src/Migration.php`):
- Extends `Illuminate\Database\Migrations\Migration`
- Use `static::write($sql)` for raw SQL migrations
- Supports `--pretend` mode for dry-run migrations
- Connection specified via `$connection` property

**WithClient Trait** (`src/WithClient.php`):
- Provides `getThisClient()` method to access ClickHouseDB\Client
- Resolves connection via Laravel's DB facade
- Used by BaseModel, Builder, and Migration

**Grammar** (`src/Grammar.php`):
- Extends Tinderbox's Grammar
- Adds SETTINGS clause compilation for SELECT queries
- Formats key=value pairs for ClickHouse SETTINGS syntax

**Service Provider** (`src/ClickhouseServiceProvider.php`):
- Registers 'clickhouse' database driver
- Must be registered BEFORE AppServiceProvider and EventServiceProvider
- Sets up event dispatcher for BaseModel

### Special Features

**Buffer Tables**: Models support separate tables for reads vs writes via:
- `$tableForInserts` - Buffer table for inserts (writes go here)
- `$tableSources` - Source table for OPTIMIZE/DELETE operations
- `$table` - Default table for SELECT queries

**Type Casting**: `$casts` property on models (currently supports 'boolean' which converts to int)

**Settings Clause**: Use `settings(['max_threads' => 3])` on queries to pass ClickHouse-specific settings

**Retries**: Configure connection-level retries via `CurlerRollingWithRetries` for network resilience

**Chunking**: Use `chunk($size, callable)` for memory-efficient processing of large result sets

## Model Pattern

Models extend `LaravelClickhouseEloquent\BaseModel` (not `Illuminate\Database\Eloquent\Model`):

```php
use LaravelClickhouseEloquent\BaseModel;

class MyModel extends BaseModel {
    protected $table = 'my_table'; // optional, auto-derived from class name
    protected $connection = 'clickhouse'; // optional, defaults to 'clickhouse'
    protected $tableForInserts = 'my_table_buffer'; // optional, for buffer engine
    protected $tableSources = 'my_table'; // optional, for OPTIMIZE/DELETE on source table
    protected $casts = ['is_active' => 'boolean']; // optional
}
```

**Common operations**:
- Insert: `MyModel::insertAssoc([['col1' => 'val1'], ['col2' => 'val2']])`
- Select: `MyModel::select(['col1', 'col2'])->where('col1', '>', 100)->getRows()`
- Delete: `MyModel::where('id', 123)->delete()` (heavy operation, not for frequent use)
- Update: `MyModel::where('id', 123)->update(['field' => 'value'])` (heavy operation)
- Optimize: `MyModel::optimize($final = false, $partition = null)`
- Truncate: `MyModel::truncate()`

## Migration Pattern

```php
class CreateMyTable extends \LaravelClickhouseEloquent\Migration {
    protected $connection = 'clickhouse'; // optional

    public function up() {
        static::write('CREATE TABLE my_table (...) ENGINE = MergeTree() ORDER BY (...)');
    }

    public function down() {
        static::write('DROP TABLE my_table');
    }
}
```

## Test Structure

Tests use a Docker-based setup that:
1. Spins up two ClickHouse server instances
2. Creates a fresh Laravel app in the PHP container
3. Copies library files into vendor directory
4. Runs migrations
5. Executes PHPUnit tests

Test organization:
- `tests/BaseTest.php` - Core functionality tests
- `tests/CastsTest.php` - Type casting tests
- `tests/Models/` - Example model classes
- `tests/migrations/` - Example migration files
- `tests/config/` - Test Laravel configuration files

## Connection Configuration

Database config in Laravel's `config/database.php`:

```php
'clickhouse' => [
    'driver' => 'clickhouse',
    'host' => env('CLICKHOUSE_HOST'),
    'port' => env('CLICKHOUSE_PORT', '8123'),
    'database' => env('CLICKHOUSE_DATABASE', 'default'),
    'username' => env('CLICKHOUSE_USERNAME', 'default'),
    'password' => env('CLICKHOUSE_PASSWORD', ''),
    'timeout_connect' => env('CLICKHOUSE_TIMEOUT_CONNECT', 2),
    'timeout_query' => env('CLICKHOUSE_TIMEOUT_QUERY', 2),
    'https' => (bool)env('CLICKHOUSE_HTTPS', null),
    'retries' => env('CLICKHOUSE_RETRIES', 0),
    'settings' => [ // optional ClickHouse settings
        'max_partitions_per_insert_block' => 300,
    ],
],
```

## Important Constraints

1. **No traditional updates**: ClickHouse doesn't support standard UPDATE operations efficiently. Use ALTER TABLE UPDATE (heavy) or versioned data patterns.

2. **No traditional deletes**: Use ALTER TABLE DELETE (heavy operation) or soft deletes with filtering.

3. **Immutable after insert**: Once saved, model instances cannot be modified via `save()`.

4. **No relationships**: This is not full Eloquent - no relationship support (belongsTo, hasMany, etc.).

5. **Bulk operations preferred**: Always prefer bulk inserts (`insertBulk`, `insertAssoc`) over single-row inserts for performance.

6. **Order matters for PRIMARY KEY**: ClickHouse table engines require ORDER BY clause in CREATE TABLE statements.

## Dependencies

- PHP >= 8.0
- Laravel/Lumen >= 7
- smi2/phpClickHouse ^1.4.2 (ClickHouse client)
- the-tinderbox/clickhouse-builder ^6.0 (query builder)
