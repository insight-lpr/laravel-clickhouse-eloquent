# CTE (Common Table Expressions) Support Design

**Date:** 2026-02-03  
**Status:** Draft  

## Overview

Add support for ClickHouse Common Table Expressions (CTEs) to the Laravel ClickHouse Eloquent query builder.

## Scope

### Included in this design:
- `withCte()` - subquery aliases: `WITH name AS (SELECT ...)`
- `withCteExpression()` - expression aliases: `WITH value AS name`
- Chainable multiple CTEs
- Support for closures, raw SQL strings, and existing Builder instances

### Deferred for later:
- `withCteRecursive()` - recursive CTEs

## API Design

### Methods

```php
// Subquery CTE: WITH name AS (SELECT ...)
public function withCte(string $name, Closure|Builder|string $query): self

// Expression CTE: WITH value AS name
public function withCteExpression(string $name, mixed $value): self

// Getter for Grammar access
public function getCtes(): array
```

### Usage Examples

**Subquery CTE:**
```php
// Using closure
$query->withCte('active_users', fn($q) => $q->select('*')->from('users')->where('active', 1));

// Using existing Builder instance
$subquery = Model::select('*')->where('active', 1);
$query->withCte('active_users', $subquery);

// Using raw SQL string
$query->withCte('active_users', 'SELECT * FROM users WHERE active = 1');
```

**Expression CTE:**
```php
// Scalar number
$query->withCteExpression('threshold', 100);

// String (auto-quoted)
$query->withCteExpression('prefix', 'user_');

// Raw expression (explicit RawColumn wrapper required)
$query->withCteExpression('today', new RawColumn('today()'));

// Subquery returning single value
$query->withCteExpression('max_id', fn($q) => $q->selectRaw('max(id)')->from('users'));
```

**Chainable multiple CTEs:**
```php
$query
    ->withCteExpression('threshold', 100)
    ->withCte('filtered', fn($q) => $q->from('data')->where('val', '>', new RawColumn('threshold')))
    ->select('*')
    ->from('filtered');
```

**Generated SQL:**
```sql
WITH 
    100 AS threshold,
    filtered AS (SELECT * FROM data WHERE val > threshold)
SELECT * FROM filtered
```

## Implementation

### File: `src/Builder.php`

**New property:**
```php
protected array $ctes = [];
```

**New methods:**

```php
public function withCte(string $name, Closure|Builder|string $query): self
{
    $sql = match (true) {
        $query instanceof Closure => $this->compileClosureToSql($query),
        $query instanceof Builder => $query->toSql(),
        default => $query, // raw SQL string
    };
    
    $this->ctes[] = [
        'name' => $name,
        'type' => 'subquery',
        'sql' => $sql,
    ];
    
    return $this;
}

public function withCteExpression(string $name, mixed $value): self
{
    $sql = match (true) {
        $value instanceof Closure => $this->compileClosureToSql($value),
        $value instanceof Builder => $value->toSql(),
        $value instanceof RawColumn => $value->getExpression(),
        is_string($value) => "'{$value}'",
        is_bool($value) => $value ? 1 : 0,
        default => $value, // numbers
    };
    
    $this->ctes[] = [
        'name' => $name,
        'type' => 'expression',
        'sql' => $sql,
    ];
    
    return $this;
}

public function getCtes(): array
{
    return $this->ctes;
}

protected function compileClosureToSql(Closure $callback): string
{
    $builder = new static($this->client);
    $callback($builder);
    return $builder->toSql();
}
```

### File: `src/Grammar.php`

**Constructor changes:**
```php
public function __construct()
{
    // Prepend 'ctes' to the beginning of select components
    array_unshift($this->selectComponents, 'ctes');
    
    // Existing: add 'settings' at the end
    $this->selectComponents[] = 'settings';
}
```

**New compilation method:**
```php
protected function compileCTEsComponent(Builder $builder): string
{
    $ctes = $builder->getCtes();
    
    if (empty($ctes)) {
        return '';
    }
    
    $parts = [];
    foreach ($ctes as $cte) {
        if ($cte['type'] === 'expression') {
            // Expression style: value AS name
            $parts[] = "{$cte['sql']} AS {$cte['name']}";
        } else {
            // Subquery style: name AS (SELECT ...)
            $parts[] = "{$cte['name']} AS ({$cte['sql']})";
        }
    }
    
    return 'WITH ' . implode(', ', $parts);
}
```

## SQL Output Examples

| Method | Input | Generated SQL |
|--------|-------|---------------|
| `withCteExpression` | `('n', 100)` | `WITH 100 AS n` |
| `withCteExpression` | `('prefix', 'user_')` | `WITH 'user_' AS prefix` |
| `withCteExpression` | `('today', new RawColumn('today()'))` | `WITH today() AS today` |
| `withCteExpression` | `('max_id', fn($q) => ...)` | `WITH (SELECT max(id) FROM t) AS max_id` |
| `withCte` | `('active', fn($q) => ...)` | `WITH active AS (SELECT ... FROM ...)` |
| Combined | Multiple calls | `WITH 100 AS n, active AS (SELECT ...)` |

## Testing Checklist

- [ ] `withCte()` with closure generates correct SQL
- [ ] `withCte()` with Builder instance generates correct SQL
- [ ] `withCte()` with raw SQL string generates correct SQL
- [ ] `withCteExpression()` with integer generates correct SQL
- [ ] `withCteExpression()` with string generates quoted SQL
- [ ] `withCteExpression()` with boolean converts to 1/0
- [ ] `withCteExpression()` with RawColumn generates unquoted SQL
- [ ] `withCteExpression()` with closure generates subquery SQL
- [ ] Multiple CTEs chain correctly
- [ ] CTE names can be referenced in main query
- [ ] Integration test: full query executes against ClickHouse

## Future Work

- `withCteRecursive()` for recursive CTEs with UNION ALL
