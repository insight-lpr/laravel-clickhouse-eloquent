# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel ClickHouse Eloquent is a PHP package that integrates ClickHouse with Laravel's database layer. It wraps two libraries:
- `smi2/phpclickhouse` - Low-level ClickHouse HTTP client
- `the-tinderbox/clickhouse-builder` - Query builder for ClickHouse

The package provides an Eloquent-like ORM (`BaseModel`) for ClickHouse, not full Eloquent compatibility.

## Running Tests

Tests require Docker with two ClickHouse instances:

```bash
docker-compose -f docker-compose.test.yaml run php sh /src/tests.bootstrap.sh
```

This bootstraps a Laravel app, runs migrations, and executes PHPUnit tests.

## Architecture

### Key Components

**Connection** (`src/Connection.php`)
- Extends `Illuminate\Database\Connection` but uses `ClickHouseDB\Client` instead of PDO
- Registered via `ClickhouseServiceProvider` as the `clickhouse` driver

**BaseModel** (`src/BaseModel.php`)
- Lightweight ORM base class (not extending Eloquent's Model)
- Uses Laravel traits: `HasAttributes`, `HidesAttributes`, `HasEvents`
- Supports: `create()`, `save()`, `insertBulk()`, `insertAssoc()`, `select()`, `where()`, `groupBy()`, `delete()`, `update()`, `optimize()`, `truncate()`

**Builder** (`src/Builder.php`)
- Extends `Tinderbox\ClickhouseBuilder\Query\BaseBuilder`
- Adds `settings()` for SETTINGS clause, `chunk()` for pagination, `update()`/`delete()` for ALTER TABLE operations

**Grammar Classes**
- `Grammar` - Extends Tinderbox grammar, adds SETTINGS compilation
- `QueryGrammar` - Converts Laravel's `?` placeholders to `:0`, `:1` format
- `SchemaGrammar` - Compiles CREATE TABLE with ClickHouse type mappings

### Table Properties

Models support three table-related properties:
- `$table` - Table for SELECT queries
- `$tableForInserts` - Table for INSERT (e.g., buffer table)
- `$tableSources` - Table for DELETE/UPDATE/OPTIMIZE (the source table behind a buffer)

### Query Flow

```
BaseModel::where()->get()
    → Builder (compiles query via Grammar)
    → ClickHouseDB\Client (HTTP request)
    → Statement/array results
```

## Development Notes

- Migrations extend `LaravelClickhouseEloquent\Migration` and use raw SQL via `static::write()`
- Column casting only supports `boolean` type currently
- Model events supported: `creating`, `created`, `saved`
- `RawColumn` wraps raw SQL expressions with optional aliasing
- `InsertArray` helper handles ClickHouse Array data type insertion
