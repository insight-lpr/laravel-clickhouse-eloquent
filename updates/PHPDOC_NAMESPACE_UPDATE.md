# PHPDoc Namespace Update

## Overview

All PHPDoc comments referencing the old `Tinderbox\Clickhouse` and `Tinderbox\ClickhouseBuilder` namespaces have been updated to use the new internal namespaces.

## Date

This update was performed to ensure PHPDoc consistency after internalizing the vendor packages.

## Changes Made

### Updated PHPDoc Annotations

All the following PHPDoc tags were updated:
- `@param`
- `@return`
- `@var`
- `@throws`

### Namespace Replacements

**Old References → New References**

1. `\Tinderbox\Clickhouse\*` → `\LaravelClickhouseEloquent\ClickhouseClient\*`
2. `\Tinderbox\ClickhouseBuilder\*` → `\LaravelClickhouseEloquent\ClickhouseBuilder\*`

### Files Updated

#### Main Application Files
- `src/Grammar.php` - Updated extends clause
- `src/RawColumn.php` - Updated use statement
- `src/BaseModel.php` - Updated use statements

#### ClickhouseClient Files
- `src/ClickhouseClient/Cluster.php` - Updated PHPDoc comments
- `src/ClickhouseClient/Server.php` - Updated PHPDoc comments
- All other ClickhouseClient files with PHPDoc references

#### ClickhouseBuilder Files
- All files with PHPDoc annotations referencing the old namespace

## Examples

### Before

```php
/**
 * @var \Tinderbox\Clickhouse\Server[]
 */
protected $servers = [];

/**
 * @param \Tinderbox\Clickhouse\Server $server
 * @return \Tinderbox\Clickhouse\Cluster
 * @throws \Tinderbox\Clickhouse\Exceptions\ClusterException
 */
public function addServer($server)
{
    // ...
}
```

### After

```php
/**
 * @var \LaravelClickhouseEloquent\ClickhouseClient\Server[]
 */
protected $servers = [];

/**
 * @param \LaravelClickhouseEloquent\ClickhouseClient\Server $server
 * @return \LaravelClickhouseEloquent\ClickhouseClient\Cluster
 * @throws \LaravelClickhouseEloquent\ClickhouseClient\Exceptions\ClusterException
 */
public function addServer($server)
{
    // ...
}
```

## Verification

### Command to Check for Old References

```bash
# Should return 0
grep -r "Tinderbox" src/ --include="*.php" | wc -l
```

### Result
✅ **0 references** - All Tinderbox references have been removed

## Benefits

1. ✅ **Consistent Namespaces** - PHPDoc matches actual code namespaces
2. ✅ **Better IDE Support** - IDEs can properly resolve type hints
3. ✅ **Accurate Documentation** - PHPDoc reflects the current codebase structure
4. ✅ **No External References** - No references to abandoned packages

## Impact

- **No Breaking Changes** - This is purely a documentation update
- **No Runtime Changes** - PHPDoc comments don't affect code execution
- **Improved Code Quality** - Better type hinting and IDE autocomplete

## Testing

All classes have been tested and verified to load correctly:
- ✓ Grammar class
- ✓ RawColumn class  
- ✓ Builder class
- ✓ ClickhouseClient classes (Server, Cluster, etc.)
- ✓ ClickhouseBuilder classes (BaseBuilder, etc.)

## Related Updates

This update is part of the complete vendor migration:
1. Vendor code internalization (VENDOR_MIGRATION.md)
2. Enum migration to native PHP 8.1+ (ENUM_MIGRATION.md)
3. PHPDoc namespace updates (this document)

---

**Update Completed Successfully** ✅
