<?php

declare(strict_types=1);

namespace LaravelClickhouseEloquent;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
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
    /** @var array Bindings from CTE subqueries, prepended before main query bindings */
    protected array $cteBindings = [];

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
    public function withCte(string $name, \Closure|self|string $query): self
    {
        $builder = match (true) {
            $query instanceof \Closure => $this->compileClosure($query),
            $query instanceof self => $query,
            default => null,
        };

        $sql = $builder ? $builder->toSql() : $query;

        if ($builder) {
            array_push($this->cteBindings, ...$builder->getBindings());
        }

        $this->ctes[] = [
            'name' => $name,
            'type' => 'subquery',
            'sql' => $sql,
        ];

        return $this;
    }

    /**
     * Compile a closure by executing it with a fresh Builder.
     *
     * @param \Closure $callback
     * @return self
     */
    protected function compileClosure(\Closure $callback): self
    {
        $builder = new static($this->client);
        $callback($builder);
        return $builder;
    }

    /**
     * Get all bindings, with CTE bindings prepended.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->cteBindings;
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
            $value instanceof \Closure => '(' . $this->compileClosure($value)->toSql() . ')',
            $value instanceof self => '(' . $value->toSql() . ')',
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
