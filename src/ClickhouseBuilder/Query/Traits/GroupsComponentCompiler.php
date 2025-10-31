<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Column;

trait GroupsComponentCompiler
{
    /**
     * Compiles groupings to string to pass this string in query.
     *
     * @param  Column[]  $columns
     */
    private function compileGroupsComponent(Builder $builder, array $columns): string
    {
        $compiledColumns = [];

        foreach ($columns as $column) {
            $compiledColumns[] = $this->compileColumn($column);
        }

        if (! empty($compiledColumns) && ! in_array('*', $compiledColumns, true)) {
            return 'GROUP BY '.implode(', ', $compiledColumns);
        } else {
            return '';
        }
    }
}
