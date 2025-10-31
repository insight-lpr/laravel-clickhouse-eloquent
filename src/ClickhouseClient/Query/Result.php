<?php

namespace LaravelClickhouseEloquent\ClickhouseClient\Query;

use LaravelClickhouseEloquent\ClickhouseClient\Exceptions\ResultException;
use LaravelClickhouseEloquent\ClickhouseClient\Query;

/**
 * Query result.
 *
 * Container for request results and statistic
 *
 * @property Query $query
 * @property array $rows
 * @property QueryStatistic $statistic
 */
class Result implements \ArrayAccess, \Countable, \Iterator
{
    /**
     * Query execution statistic.
     *
     * @var \LaravelClickhouseEloquent\ClickhouseClient\Query\QueryStatistic
     */
    protected $statistic;

    /**
     * Result of query execution.
     *
     * @var array
     */
    protected $rows;

    /**
     * Query which was executed to get this result.
     *
     * @var Query
     */
    protected $query;

    /**
     * Current index for Iterator interface.
     *
     * @var int
     */
    protected $current = 0;

    /**
     * Result constructor.
     */
    public function __construct(Query $query, array $rows, QueryStatistic $statistic)
    {
        $this->setQuery($query);
        $this->setRows($rows);
        $this->setStatistic($statistic);
    }

    /**
     * Sets query.
     */
    protected function setQuery(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Sets statistic.
     */
    protected function setStatistic(QueryStatistic $statistic)
    {
        $this->statistic = $statistic;
    }

    /**
     * Sets rows.
     */
    protected function setRows(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Returns rows.
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Returns query.
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * Returns statistic.
     */
    public function getStatistic(): QueryStatistic
    {
        return $this->statistic;
    }

    /**
     * Getter to simplify access to rows and statistic.
     *
     * @param  string  $name
     * @return mixed
     *
     * @throws \LaravelClickhouseEloquent\ClickhouseClient\Exceptions\ResultException
     */
    public function __get($name)
    {
        $method = 'get'.ucfirst($name);

        if (method_exists($this, $method)) {
            return call_user_func([$this, $method]);
        }

        throw ResultException::propertyNotExists($name);
    }

    /*
     * ArrayAccess
     */

    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->rows[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw ResultException::isReadonly();
    }

    public function offsetUnset($offset)
    {
        throw ResultException::isReadonly();
    }

    /*
     * Iterator
     */

    public function current()
    {
        return $this->rows[$this->current];
    }

    public function next()
    {
        $this->current++;
    }

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        return isset($this->rows[$this->current]);
    }

    public function rewind()
    {
        $this->current = 0;
    }

    /*
     * Countable
     */

    public function count()
    {
        return count($this->rows);
    }
}
