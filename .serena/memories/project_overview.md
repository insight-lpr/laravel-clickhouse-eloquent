# Laravel ClickHouse Eloquent - Project Overview

## Purpose
A PHP package that integrates ClickHouse with Laravel's database layer, providing an Eloquent-like ORM for ClickHouse databases.

## Core Functionality
- Wraps `smi2/phpclickhouse` (low-level HTTP client) and `the-tinderbox/clickhouse-builder` (query builder)
- Provides `BaseModel` class for Eloquent-like model operations
- Supports Laravel 7+ and PHP 8.0+
- Registered as `clickhouse` driver via `ClickhouseServiceProvider`

## Key Components

### Connection (`src/Connection.php`)
- Extends `Illuminate\Database\Connection` using `ClickHouseDB\Client` instead of PDO

### BaseModel (`src/BaseModel.php`)
- Lightweight ORM base class (not full Eloquent)
- Uses Laravel traits: `HasAttributes`, `HidesAttributes`, `HasEvents`
- Key methods: `create()`, `save()`, `insertBulk()`, `insertAssoc()`, `select()`, `where()`, `groupBy()`, `delete()`, `update()`, `optimize()`, `truncate()`

### Builder (`src/Builder.php`)
- Extends `Tinderbox\ClickhouseBuilder\Query\BaseBuilder`
- Adds `settings()` for SETTINGS clause, `chunk()` for pagination

### Grammar Classes
- `Grammar` - Extends Tinderbox grammar, adds SETTINGS compilation
- `QueryGrammar` - Converts `?` placeholders to `:0`, `:1` format
- `SchemaGrammar` - Compiles CREATE TABLE with ClickHouse type mappings

## Table Properties
Models support three table-related properties:
- `$table` - Table for SELECT queries
- `$tableForInserts` - Table for INSERT (e.g., buffer table)
- `$tableSources` - Table for DELETE/UPDATE/OPTIMIZE

## Query Flow
```
BaseModel::where()->get()
    → Builder (compiles query via Grammar)
    → ClickHouseDB\Client (HTTP request)
    → Statement/array results
```
