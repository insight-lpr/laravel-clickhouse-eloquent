<?php

declare(strict_types=1);

namespace LaravelClickhouseEloquent;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use LaravelClickhouseEloquent\Exceptions\QueryException;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;
use Tinderbox\ClickhouseBuilder\Query\Expression;

class Builder extends BaseBuilder
{

    use WithClient;

    /** @var string */
    protected $tableSources;
    /** @var Client */
    protected $client;
    protected $settings = [];
    /** @var array */
    protected $ctes = [];

    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected ?string $connection = Connection::DEFAULT_NAME;

    public function __construct(Client $client = null)
    {
        $this->grammar = new Grammar();
        $this->client = $client ?? $this->getThisClient();
    }

    /**
     * Set the SETTINGS clause for the SELECT statement.
     * @link https://clickhouse.com/docs/en/sql-reference/statements/select#settings-in-select-query
     * @param array $settings For example: [max_threads => 3]
     * @return $this
     */
    public function settings(array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return Statement
     */
    public function get(): Statement
    {
        return $this->client->select($this->toSql());
    }

    /**
     * @return array
     */
    public function getRows(): array
    {
        return $this->get()->rows();
    }

    /**
     * Paginate the query using Laravel-style paginator.
     *
     * @param  int|callable  $perPage
     * @param  array|string|\Illuminate\Contracts\Support\Arrayable  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  int|callable|null  $total
     * @return LengthAwarePaginator
     */
    public function paginate(int|callable $perPage = 15, array|string|Arrayable $columns = ['*'], string $pageName = 'page', ?int $page = null, int|callable|null $total = null): LengthAwarePaginator
    {
        $page = $page ?? Paginator::resolveCurrentPage($pageName);
        $page = max(1, (int) $page);

        $total = $this->resolveValue($total);
        if (is_null($total)) {
            $total = $this->getCountForPagination();
        }
        $total = (int) $total;

        $perPage = $this->resolveValue($perPage, [$total]);
        $perPage = (int) $perPage;
        $perPage = $perPage > 0 ? $perPage : 15;

        $columns = $this->normalizeColumns($columns);

        $items = $total ? $this->getPaginatedItems($page, $perPage, $columns) : [];

        return new LengthAwarePaginator(
            new Collection($items),
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Get the raw SQL representation of the query (bindings are already embedded).
     *
     * @return string
     */
    public function toRawSql(): string
    {
        return $this->toSql();
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     */
    public function chunk(int $count, callable $callback): void
    {
        $offset = 0;
        do {
            $rows = $this->limit($count, $offset)->getRows();
            $callback($rows);
            $offset += $count;
        } while ($rows);
    }

    /**
     * Apply limit/offset for a specific pagination page.
     */
    protected function forPage(int $page, int $perPage): self
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return $this->limit($perPage, $offset);
    }

    /**
     * Normalize the columns parameter into an array.
     *
     * @param  array|string|\Illuminate\Contracts\Support\Arrayable  $columns
     * @return array
     */
    protected function normalizeColumns(array|string|Arrayable $columns): array
    {
        if ($columns instanceof Arrayable) {
            return $columns->toArray();
        }

        return is_array($columns) ? $columns : [$columns];
    }

    /**
     * Compile the scoped query used for paginated results.
     *
     * @param  array  $columns
     * @return array
     */
    protected function getPaginatedItems(int $page, int $perPage, array $columns): array
    {
        $query = $this->cloneWithout(['limit' => null]);

        if ($columns !== ['*']) {
            $query->select(...$columns);
        }

        return $query->forPage($page, $perPage)->getRows();
    }

    /**
     * Get the total count to drive pagination.
     *
     * @return int
     */
    protected function getCountForPagination(): int
    {
        $countQuery = $this->getCountQuery();
        $rows = $countQuery->getRows();

        if (!empty($countQuery->getGroups())) {
            return count($rows);
        }

        return (int) ($rows[0]['count'] ?? 0);
    }

    /**
     * Resolve a value or callable helper.
     *
     * @param  mixed  $value
     * @param  array<mixed>  $arguments
     * @return mixed
     */
    protected function resolveValue(mixed $value, array $arguments = []): mixed
    {
        return is_callable($value) ? $value(...$arguments) : $value;
    }

    /**
     * For delete query
     * @param string $table
     * @return $this
     */
    public function setSourcesTable(string $table): self
    {
        $this->tableSources = $table;

        return $this;
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function delete(): Statement
    {
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $sql = "ALTER TABLE $table DELETE " . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function update(array $values): Statement
    {
        if (empty($values)) {
            throw QueryException::cannotUpdateEmptyValues();
        }
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $set = [];
        foreach ($values as $key => $value) {
            $set[] = "`$key` = " . $this->grammar->wrap($value);
        }
        $sql = "ALTER TABLE $table UPDATE " . implode(', ', $set) . ' '
            . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

    public function newQuery(): self
    {
        return new static($this->client);
    }

    /**
     * Get the CTEs for the query.
     *
     * @return array
     */
    public function getCtes(): array
    {
        return $this->ctes;
    }

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
}
