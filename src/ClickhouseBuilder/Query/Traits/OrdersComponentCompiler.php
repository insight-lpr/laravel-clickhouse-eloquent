<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;

trait OrdersComponentCompiler
{
    /**
     * Compiles order to string to pass this string in query.
     */
    public function compileOrdersComponent(Builder $builder, array $orders): string
    {
        $columns = [];

        foreach ($orders as $order) {
            [$column, $direction, $collate] = $order;

            $columns[] = "{$this->compileColumn($column)}".
                ($direction ? " {$direction}" : '').
                ($collate ? " COLLATE {$this->wrap($collate)}" : '');
        }

        return 'ORDER BY '.implode(', ', $columns);
    }
}
