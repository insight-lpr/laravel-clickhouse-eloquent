# Vendor Code Migration

## Overview

This document describes the migration of abandoned vendor packages into the application codebase.

## Date

This migration was performed to internalize the abandoned packages and maintain control over their code.

## Packages Migrated

### 1. the-tinderbox/clickhouse-builder (v6.0)

**Reason for Migration**: The package has been abandoned and is no longer maintained.

**Original Namespace**: `Tinderbox\ClickhouseBuilder`

**New Namespace**: `LaravelClickhouseEloquent\ClickhouseBuilder`

**New Location**: `src/ClickhouseBuilder/`

**Dependencies**: 
- `myclabs/php-enum` (^1.5) - Still required, kept as composer dependency
- `the-tinderbox/clickhouse-php-client` (^3.0) - Also migrated (see below)

### 2. the-tinderbox/clickhouse-php-client (v3.1.0)

**Reason for Migration**: Required dependency of clickhouse-builder, also abandoned.

**Original Namespace**: `Tinderbox\Clickhouse`

**New Namespace**: `LaravelClickhouseEloquent\ClickhouseClient`

**New Location**: `src/ClickhouseClient/`

**Dependencies**:
- `guzzlehttp/guzzle` (^6.0|^7.0) - Still required, kept as composer dependency

## Changes Made

### 1. Directory Structure

Created new directories:
- `src/ClickhouseBuilder/`
  - `Exceptions/`
  - `Query/`
    - `Enums/`
    - `Traits/`
  - `functions.php`

- `src/ClickhouseClient/`
  - `Common/`
  - `Exceptions/`
  - `Interfaces/`
  - `Query/`
  - `Support/`
  - `Transport/`

### 2. Namespace Updates

All namespace declarations and use statements were updated:

**Before**:
```php
namespace Tinderbox\ClickhouseBuilder;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
```

**After**:
```php
namespace LaravelClickhouseEloquent\ClickhouseBuilder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder;
```

### 3. Composer Changes

**Removed Dependencies**:
- `the-tinderbox/clickhouse-builder` (^6.0)

**Updated `composer.json`**:
```json
{
  "autoload": {
    "psr-4": {
      "LaravelClickhouseEloquent\\": "src/"
    },
    "files": [
      "src/ClickhouseBuilder/functions.php"
    ]
  }
}
```

### 4. Application Code Updates

Updated `src/Builder.php` to reference the new namespace:

**Before**:
```php
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
```

**After**:
```php
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder;
```

## Files Migrated

### ClickhouseBuilder (42 files)

- `functions.php`
- `Exceptions/` (4 files)
  - `BuilderException.php`
  - `Exception.php`
  - `GrammarException.php`
  - `NotSupportedException.php`
- `Query/` (37 files)
  - Main query builder classes
  - Enums for Format, JoinStrict, JoinType, Operator, OrderDirection
  - Various compiler traits for query components

### ClickhouseClient (25 files)

- `Client.php`
- `Cluster.php`
- `Query.php`
- `Server.php`
- `ServerProvider.php`
- `Common/` (8 files)
- `Exceptions/` (6 files)
- `Interfaces/` (2 files)
- `Query/` (2 files)
- `Support/` (1 file)
- `Transport/` (1 file)

## Testing

After migration, ensure to:

1. Run `composer dump-autoload` to regenerate autoloader
2. Run existing test suite to verify functionality
3. Test all ClickHouse query operations
4. Verify builder methods work correctly

## Maintenance

Going forward:

1. **Bug Fixes**: Any bugs found in the migrated code can now be fixed directly in `src/ClickhouseBuilder/` and `src/ClickhouseClient/`
2. **Feature Additions**: New features can be added to the internal codebase
3. **PHP Version Updates**: Code can be updated to use newer PHP features as needed
4. **Dependencies**: Monitor and update remaining dependencies (guzzlehttp/guzzle, myclabs/php-enum)

## Rollback

To rollback this migration (not recommended):

1. Restore `composer.json` to include:
   ```json
   "require": {
     "the-tinderbox/clickhouse-builder": "^6.0"
   }
   ```

2. Remove the autoload files entry:
   ```json
   "files": [
     "src/ClickhouseBuilder/functions.php"
   ]
   ```

3. Delete directories:
   - `src/ClickhouseBuilder/`
   - `src/ClickhouseClient/`

4. Update `src/Builder.php` to use original namespace:
   ```php
   use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
   ```

5. Run `composer update`

## References

- Original clickhouse-builder: https://github.com/the-tinderbox/clickhouse-builder
- Original clickhouse-php-client: https://github.com/the-tinderbox/ClickhouseClient

## Notes

- The Laravel-specific integration classes in `src/ClickhouseBuilder/Integrations/Laravel/` were copied but are not currently being used by this application
- Helper functions in `functions.php` are now autoloaded globally
- All type hints and return types have been preserved from the original code