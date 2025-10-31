<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query;

class From
{
    /**
     * Table name.
     *
     * @var Identifier|Expression|null
     */
    private $table;

    /**
     * Table alias.
     *
     * @var Identifier|null
     */
    private $alias;

    /**
     * Final option.
     *
     * @var bool|null
     */
    private $final;

    /**
     * BaseBuilder is needed to pass bindings in main query.
     *
     * @var BaseBuilder
     */
    private $query;

    /**
     * Query which was made with sub-query BaseBuilder.
     *
     * @var BaseBuilder|null
     */
    private $subQuery;

    /**
     * From constructor.
     */
    public function __construct(BaseBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Set table name.
     *
     * @param  string|Expression  $table
     */
    public function table($table): self
    {
        if (is_string($table)) {
            $table = new Identifier($table);
        }

        $this->table = $table;

        return $this;
    }

    /**
     * Set table alias.
     */
    public function as(string $alias): self
    {
        $this->alias = new Identifier($alias);

        return $this;
    }

    /**
     * Set final option.
     *
     * Used in CollapsingMergeTree tables
     */
    public function final(bool $isFinal = true): self
    {
        $this->final = $isFinal;

        return $this;
    }

    /**
     * Use remote function to get data from remote server without table with Distributed engine.
     */
    public function remote(string $expression, string $database, string $table, ?string $user = null, ?string $password = null): self
    {
        $remote = "remote('{$expression}', {$database}, {$table}";

        if (! is_null($user)) {
            $remote .= ", {$user}";
        }

        if (! is_null($password)) {
            $remote .= ", {$password}";
        }

        $remote .= ')';

        return $this->table(new Expression($remote));
    }

    /**
     * Creates temp table with Merge engine
     * Structure takes from first table in regular expression.
     */
    public function merge(string $database, string $regexp): self
    {
        return $this->table(new Expression("merge({$database}, '{$regexp}')"));
    }

    /**
     * Executes sub-query in from statement.
     *
     * @param  \Closure|BaseBuilder|null  $query
     * @return From|BaseBuilder
     */
    public function query($query = null)
    {
        if (is_null($query)) {
            return $this->subQuery();
        }

        if ($query instanceof \Closure) {
            $query = tp($this->query->newQuery(), $query);
        }

        if ($query instanceof BaseBuilder) {
            if (is_null($this->alias) && ! is_null($this->table)) {
                $this->as($this->table);
            }

            $this->table(new Expression("({$query->toSql()})"));
        }

        return $this;
    }

    /**
     * Get sub-query.
     */
    public function subQuery(): BaseBuilder
    {
        return $this->subQuery = $this->query->newQuery();
    }

    /**
     * Get table name.
     *
     * @return Expression|Identifier|null
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get alias.
     */
    public function getAlias(): ?Identifier
    {
        return $this->alias;
    }

    /**
     * Get final option.
     */
    public function getFinal(): ?bool
    {
        return $this->final;
    }

    /**
     * Get sub-query BaseBuilder.
     */
    public function getSubQuery(): ?BaseBuilder
    {
        return $this->subQuery;
    }
}
