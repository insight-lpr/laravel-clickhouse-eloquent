<?php

namespace LaravelClickhouseEloquent\ClickhouseClient\Interfaces;

use LaravelClickhouseEloquent\ClickhouseClient\Query;
use LaravelClickhouseEloquent\ClickhouseClient\Query\Result;

/**
 * Interface describes transport.
 */
interface TransportInterface
{
    /**
     * Executes queries which should not return result.
     *
     * Queries runs asyn
     *
     * @param  Query[]  $queries
     */
    public function write(array $queries, int $concurrency = 5): array;

    /**
     * Executes queries which returns result of any select expression.
     *
     *
     * @return Result[]
     */
    public function read(array $queries, int $concurrency = 5): array;
}
