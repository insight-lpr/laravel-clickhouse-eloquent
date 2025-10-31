# Enum Migration to Native PHP 8.1+ Enums

## Overview

All enums in the ClickhouseBuilder package have been migrated from `myclabs/php-enum` to native PHP 8.1+ enums.

## Date

This migration was performed to remove the dependency on the `myclabs/php-enum` package and use modern PHP features.

## Enums Migrated

### 1. Format
**Location**: `src/ClickhouseBuilder/Query/Enums/Format.php`
**Type**: `enum Format: string`
**Cases**: 25 format types (CSV, JSON, TabSeparated, etc.)

### 2. JoinType
**Location**: `src/ClickhouseBuilder/Query/Enums/JoinType.php`
**Type**: `enum JoinType: string`
**Cases**: INNER, LEFT, RIGHT, FULL, CROSS, ASOF

### 3. JoinStrict
**Location**: `src/ClickhouseBuilder/Query/Enums/JoinStrict.php`
**Type**: `enum JoinStrict: string`
**Cases**: ALL, ANY

### 4. Operator
**Location**: `src/ClickhouseBuilder/Query/Enums/Operator.php`
**Type**: `enum Operator: string`
**Cases**: 24 operators (=, !=, <, >, LIKE, IN, BETWEEN, etc.)

### 5. OrderDirection
**Location**: `src/ClickhouseBuilder/Query/Enums/OrderDirection.php`
**Type**: `enum OrderDirection: string`
**Cases**: ASC, DESC

## Changes Made

### Before (MyCLabs Enum)

```php
use MyCLabs\Enum\Enum;

final class Format extends Enum
{
    public const CSV = 'CSV';
    public const JSON = 'JSON';
}

// Usage
$format = Format::CSV;  // Returns string 'CSV'
Format::isValid('CSV'); // Returns true
```

### After (Native PHP Enum)

```php
enum Format: string
{
    case CSV = 'CSV';
    case JSON = 'JSON';
    
    public static function isValid(mixed $value): bool
    {
        return self::tryFrom($value) !== null;
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getKey(): string
    {
        return $this->name;
    }
}

// Usage
$format = Format::CSV->value;  // Returns string 'CSV'
Format::isValid('CSV');        // Returns true
```

## Code Updates

All enum constant references were updated to use `->value`:

### Example Changes

**Before:**
```php
public function insertFile(array $columns, $file, string $format = Format::CSV)
{
    // ...
}

$this->join($table, JoinStrict::ALL, JoinType::LEFT, $using);
```

**After:**
```php
public function insertFile(array $columns, $file, string $format = Format::CSV->value)
{
    // ...
}

$this->join($table, JoinStrict::ALL->value, JoinType::LEFT->value, $using);
```

## Backward Compatibility

### Compatible Methods

The following methods were added to maintain compatibility with MyCLabs Enum API:

- `isValid(mixed $value): bool` - Check if a value is a valid enum value
- `getValue(): string` - Get the enum value as a string (equivalent to `->value`)
- `getKey(): string` - Get the enum case name (equivalent to `->name`)

### Breaking Changes

**None** - The migration is fully backward compatible for all existing usage patterns in the codebase.

## Benefits

✅ **No External Dependency**: Removed `myclabs/php-enum` package
✅ **Modern PHP**: Using native PHP 8.1+ features
✅ **Better IDE Support**: Native enums have better autocomplete and type checking
✅ **Performance**: Native enums are faster than class-based enums
✅ **Type Safety**: Stronger type checking at compile time

## Testing

### Manual Testing

```php
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Operator;

// Test enum values
assert(Format::CSV->value === 'CSV');
assert(Operator::EQUALS->value === '=');

// Test isValid method
assert(Format::isValid('CSV') === true);
assert(Format::isValid('INVALID') === false);

// Test in function parameters
function test(string $format = Format::CSV->value) {
    return $format;
}
```

## Files Modified

- `src/ClickhouseBuilder/Query/Enums/*.php` (5 enum files)
- `src/ClickhouseBuilder/Query/BaseBuilder.php`
- `src/ClickhouseBuilder/Query/Builder.php`
- `src/ClickhouseBuilder/Query/Grammar.php`
- `src/ClickhouseBuilder/Query/JoinClause.php`
- `src/ClickhouseBuilder/Query/ArrayJoinClause.php`
- `src/ClickhouseBuilder/Query/Traits/TwoElementsLogicExpressionsCompiler.php`
- `composer.json` (removed myclabs/php-enum dependency)

## Composer Changes

**Removed:**
```json
{
  "require": {
    "myclabs/php-enum": "^1.5"
  }
}
```

## Rollback

To rollback this change (not recommended):

1. Add back the myclabs/php-enum dependency in composer.json
2. Restore enum files from git history
3. Remove `->value` from all enum constant references throughout the codebase
4. Run `composer update`

## Notes

- All enum cases are backed by string values
- The `->value` property must be used when the enum is used as a string
- The `isValid()` method uses native `tryFrom()` which is efficient
- Native enums are objects, not strings, so they can't be used directly in string contexts

## References

- PHP Enums Documentation: https://www.php.net/manual/en/language.enumerations.php
- RFC: https://wiki.php.net/rfc/enumerations
