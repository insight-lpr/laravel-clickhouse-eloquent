# CTE Support Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add CTE (Common Table Expression) support to the Laravel ClickHouse Eloquent query builder.

**Architecture:** Add `$ctes` array to Builder, implement `withCte()` and `withCteExpression()` methods that store CTE definitions, then compile them in Grammar via a new `compileCTEsComponent()` method prepended to select components.

**Tech Stack:** PHP 8.x, Laravel, Tinderbox ClickHouse Builder

---

## Task 1: Add CTE Test File

**Files:**
- Create: `tests/CteTest.php`

**Step 1: Create the test file with basic structure**

```php
<?php

namespace Tests;

use LaravelClickhouseEloquent\Builder;
use LaravelClickhouseEloquent\RawColumn;
use Tests\Models\Example;

class CteTest extends TestCase
{
    /**
     * Test withCte() with a closure generates correct SQL.
     */
    public function testWithCteClosure(): void
    {
        $query = Example::select()
            ->withCte('active', function (Builder $q) {
                $q->select('*')->from('users')->where('active', '=', 1);
            })
            ->from('active');

        $sql = $query->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('active AS (SELECT', $sql);
        $this->assertStringContainsString('FROM `active`', $sql);
    }

    /**
     * Test withCte() with raw SQL string generates correct SQL.
     */
    public function testWithCteRawString(): void
    {
        $query = Example::select()
            ->withCte('filtered', 'SELECT * FROM examples WHERE f_int > 10')
            ->from('filtered');

        $sql = $query->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('filtered AS (SELECT * FROM examples WHERE f_int > 10)', $sql);
    }

    /**
     * Test withCte() with existing Builder instance generates correct SQL.
     */
    public function testWithCteBuilderInstance(): void
    {
        $subquery = Example::select('f_int', 'f_string')->where('f_int', '>', 5);

        $query = Example::select()
            ->withCte('sub', $subquery)
            ->from('sub');

        $sql = $query->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('sub AS (SELECT', $sql);
    }

    /**
     * Test withCteExpression() with integer generates correct SQL.
     */
    public function testWithCteExpressionInteger(): void
    {
        $query = Example::select()
            ->withCteExpression('threshold', 100)
            ->where('f_int', '>', new RawColumn('threshold'));

        $sql = $query->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('100 AS threshold', $sql);
    }

    /**
     * Test withCteExpression() with string generates quoted SQL.
     */
    public function testWithCteExpressionString(): void
    {
        $query = Example::select()
            ->withCteExpression('prefix', 'user_');

        $sql = $query->toSql();

        $this->assertStringContainsString("'user_' AS prefix", $sql);
    }

    /**
     * Test withCteExpression() with boolean converts to 1/0.
     */
    public function testWithCteExpressionBoolean(): void
    {
        $queryTrue = Example::select()->withCteExpression('flag', true);
        $queryFalse = Example::select()->withCteExpression('flag', false);

        $this->assertStringContainsString('1 AS flag', $queryTrue->toSql());
        $this->assertStringContainsString('0 AS flag', $queryFalse->toSql());
    }

    /**
     * Test withCteExpression() with RawColumn generates unquoted SQL.
     */
    public function testWithCteExpressionRawColumn(): void
    {
        $query = Example::select()
            ->withCteExpression('today', new RawColumn('today()'));

        $sql = $query->toSql();

        $this->assertStringContainsString('today() AS today', $sql);
    }

    /**
     * Test withCteExpression() with closure generates subquery SQL.
     */
    public function testWithCteExpressionClosure(): void
    {
        $query = Example::select()
            ->withCteExpression('max_val', function (Builder $q) {
                $q->selectRaw('max(f_int)')->from('examples');
            });

        $sql = $query->toSql();

        $this->assertStringContainsString('(SELECT max(f_int) FROM `examples`) AS max_val', $sql);
    }

    /**
     * Test multiple CTEs chain correctly.
     */
    public function testMultipleCtes(): void
    {
        $query = Example::select()
            ->withCteExpression('threshold', 50)
            ->withCte('filtered', function (Builder $q) {
                $q->select('*')->from('examples')->where('f_int', '>', new RawColumn('threshold'));
            })
            ->from('filtered');

        $sql = $query->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('50 AS threshold', $sql);
        $this->assertStringContainsString('filtered AS (SELECT', $sql);
        // Verify order: expressions before subqueries in SQL
        $thresholdPos = strpos($sql, '50 AS threshold');
        $filteredPos = strpos($sql, 'filtered AS (SELECT');
        $this->assertLessThan($filteredPos, $thresholdPos);
    }

    /**
     * Test CTE with SETTINGS clause - CTE should come before SELECT, SETTINGS after.
     */
    public function testCteWithSettings(): void
    {
        $query = Example::select()
            ->withCteExpression('n', 10)
            ->settings(['max_threads' => 2]);

        $sql = $query->toSql();

        // WITH should be at the beginning
        $this->assertStringStartsWith('WITH', trim($sql));
        // SETTINGS should be at the end
        $this->assertStringContainsString('SETTINGS max_threads=2', $sql);
    }
}
```

**Step 2: Run tests to verify they fail**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php`

Expected: FAIL - methods `withCte()` and `withCteExpression()` do not exist

**Step 3: Commit test file**

```bash
git add tests/CteTest.php
git commit -m "test: add failing tests for CTE support"
```

---

## Task 2: Add CTE Properties and Getter to Builder

**Files:**
- Modify: `src/Builder.php`

**Step 1: Add the `$ctes` property and `getCtes()` method**

Add after line 19 (after `protected $settings = [];`):

```php
/** @var array */
protected $ctes = [];
```

Add at the end of the class (before the closing brace):

```php
/**
 * Get the CTEs for the query.
 *
 * @return array
 */
public function getCtes(): array
{
    return $this->ctes;
}
```

**Step 2: Run tests to verify they still fail (but for different reason)**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php`

Expected: FAIL - `withCte()` method still doesn't exist

**Step 3: Commit**

```bash
git add src/Builder.php
git commit -m "feat(builder): add ctes property and getCtes method"
```

---

## Task 3: Implement `withCte()` Method

**Files:**
- Modify: `src/Builder.php`

**Step 1: Add the `withCte()` method**

Add after the `getCtes()` method:

```php
/**
 * Add a Common Table Expression (CTE) subquery.
 *
 * WITH name AS (SELECT ...)
 *
 * @param string $name The CTE alias name
 * @param \Closure|Builder|string $query The subquery as closure, Builder, or raw SQL
 * @return $this
 */
public function withCte(string $name, \Closure|Builder|string $query): self
{
    $sql = match (true) {
        $query instanceof \Closure => $this->compileClosureToSql($query),
        $query instanceof Builder => $query->toSql(),
        default => $query,
    };

    $this->ctes[] = [
        'name' => $name,
        'type' => 'subquery',
        'sql' => $sql,
    ];

    return $this;
}

/**
 * Compile a closure to SQL by executing it with a fresh Builder.
 *
 * @param \Closure $callback
 * @return string
 */
protected function compileClosureToSql(\Closure $callback): string
{
    $builder = new static($this->client);
    $callback($builder);
    return $builder->toSql();
}
```

**Step 2: Run specific tests**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php --filter="testWithCteClosure|testWithCteRawString|testWithCteBuilderInstance"`

Expected: FAIL - Grammar doesn't compile CTEs yet

**Step 3: Commit**

```bash
git add src/Builder.php
git commit -m "feat(builder): add withCte method for subquery CTEs"
```

---

## Task 4: Implement `withCteExpression()` Method

**Files:**
- Modify: `src/Builder.php`

**Step 1: Add the import for RawColumn at the top of the file**

After line 8 (`use LaravelClickhouseEloquent\Exceptions\QueryException;`), add:

```php
use Tinderbox\ClickhouseBuilder\Query\Expression;
```

**Step 2: Add the `withCteExpression()` method**

Add after the `compileClosureToSql()` method:

```php
/**
 * Add a Common Table Expression (CTE) expression alias.
 *
 * WITH value AS name
 *
 * @param string $name The CTE alias name
 * @param mixed $value Scalar, RawColumn/Expression, Closure, or Builder
 * @return $this
 */
public function withCteExpression(string $name, mixed $value): self
{
    $sql = match (true) {
        $value instanceof \Closure => '(' . $this->compileClosureToSql($value) . ')',
        $value instanceof Builder => '(' . $value->toSql() . ')',
        $value instanceof Expression => $value->getValue(),
        is_string($value) => "'" . addslashes($value) . "'",
        is_bool($value) => $value ? '1' : '0',
        default => (string) $value,
    };

    $this->ctes[] = [
        'name' => $name,
        'type' => 'expression',
        'sql' => $sql,
    ];

    return $this;
}
```

**Step 3: Run expression tests**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php --filter="testWithCteExpression"`

Expected: FAIL - Grammar doesn't compile CTEs yet

**Step 4: Commit**

```bash
git add src/Builder.php
git commit -m "feat(builder): add withCteExpression method for expression CTEs"
```

---

## Task 5: Implement Grammar CTE Compilation

**Files:**
- Modify: `src/Grammar.php`

**Step 1: Update constructor to add 'ctes' component at the beginning**

Replace the entire Grammar class:

```php
<?php

namespace LaravelClickhouseEloquent;

class Grammar extends \Tinderbox\ClickhouseBuilder\Query\Grammar
{
    public function __construct()
    {
        // Prepend 'ctes' to beginning of select components
        array_unshift($this->selectComponents, 'ctes');
        // Add 'settings' to end of select components
        $this->selectComponents[] = 'settings';
    }

    /**
     * Compile the CTEs component of a SELECT query.
     *
     * @param Builder $builder
     * @param array $ctes
     * @return string
     */
    public function compileCTEsComponent(Builder $builder, array $ctes): string
    {
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

    /**
     * Compile the SETTINGS component of a SELECT query.
     *
     * @param Builder $builder
     * @param array $settings
     * @return string
     */
    public function compileSettingsComponent($builder, array $settings): string
    {
        if (empty($settings)) {
            return '';
        }

        $strAr = [];
        foreach ($settings as $k => $v) {
            $strAr[] = is_int($v) ? "$k=$v" : "$k='$v'";
        }

        return 'SETTINGS ' . implode(', ', $strAr);
    }
}
```

**Step 2: Run all CTE tests**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php`

Expected: All tests PASS

**Step 3: Commit**

```bash
git add src/Grammar.php
git commit -m "feat(grammar): add CTE compilation support"
```

---

## Task 6: Run Full Test Suite

**Files:** None (verification only)

**Step 1: Run all tests to ensure no regressions**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit`

Expected: All tests PASS

**Step 2: If any tests fail, fix them before proceeding**

**Step 3: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "fix: address any test regressions from CTE changes"
```

---

## Task 7: Integration Test with ClickHouse

**Files:**
- Modify: `tests/CteTest.php`

**Step 1: Add integration test that executes against real ClickHouse**

Add this test method to `CteTest.php`:

```php
/**
 * Integration test: execute CTE query against ClickHouse.
 */
public function testCteExecutesAgainstClickhouse(): void
{
    // Ensure we have test data
    Example::truncate();
    usleep(1e4);
    Example::insertAssoc([
        ['f_int' => 10, 'f_string' => 'a', 'f_int2' => 1, 'created_at' => date('Y-m-d H:i:s')],
        ['f_int' => 20, 'f_string' => 'b', 'f_int2' => 2, 'created_at' => date('Y-m-d H:i:s')],
        ['f_int' => 30, 'f_string' => 'c', 'f_int2' => 3, 'created_at' => date('Y-m-d H:i:s')],
    ]);
    usleep(1e4);

    // Query using CTE
    $rows = Example::select()
        ->withCteExpression('threshold', 15)
        ->withCte('filtered', function (Builder $q) {
            $q->select('f_int', 'f_string')
              ->from('examples')
              ->where('f_int', '>', new RawColumn('threshold'));
        })
        ->newQuery()
        ->select('*')
        ->from('filtered')
        ->getRows();

    $this->assertCount(2, $rows);
    $this->assertEquals(20, $rows[0]['f_int']);
    $this->assertEquals(30, $rows[1]['f_int']);
}
```

**Step 2: Run integration test**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit tests/CteTest.php --filter="testCteExecutesAgainstClickhouse"`

Expected: PASS

**Step 3: Commit**

```bash
git add tests/CteTest.php
git commit -m "test: add CTE integration test with ClickHouse"
```

---

## Task 8: Final Verification and Cleanup

**Step 1: Run full test suite one more time**

Run: `docker-compose -f docker-compose.test.yaml run php vendor/bin/phpunit`

Expected: All tests PASS

**Step 2: Review changes**

```bash
git log --oneline -10
git diff HEAD~7..HEAD --stat
```

**Step 3: Tag completion (optional)**

```bash
git tag -a v1.x.x -m "Add CTE support"
```

---

## Summary of Changes

| File | Changes |
|------|---------|
| `src/Builder.php` | Added `$ctes` property, `getCtes()`, `withCte()`, `withCteExpression()`, `compileClosureToSql()` |
| `src/Grammar.php` | Updated constructor to prepend 'ctes' component, added `compileCTEsComponent()` |
| `tests/CteTest.php` | New test file with 11 test methods covering all CTE functionality |
