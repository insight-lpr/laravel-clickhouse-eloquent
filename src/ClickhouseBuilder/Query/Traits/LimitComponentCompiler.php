<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Limit;

trait LimitComponentCompiler
{
    /**
     * Compiles limit to string to pass this string in query.
     */
    public function compileLimitComponent(Builder $builder, Limit $limit): string
    {
        $limitElements = [];

        if (! is_null($limit->getOffset())) {
            $limitElements[] = $limit->getOffset();
        }

        if (! is_null($limit->getLimit())) {
            $limitElements[] = $limit->getLimit();
        }

        return 'LIMIT '.implode(', ', $limitElements);
    }
}
