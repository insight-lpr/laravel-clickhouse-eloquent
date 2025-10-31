<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Tuple;

trait TupleCompiler
{
    /**
     * Compiles tuple to string to use this string in query.
     */
    public function compileTuple(Tuple $tuple): string
    {
        return implode(', ', array_map([$this, 'wrap'], $tuple->getElements()));
    }
}
