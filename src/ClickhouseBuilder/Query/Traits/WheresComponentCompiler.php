<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\TwoElementsLogicExpression;

trait WheresComponentCompiler
{
    /**
     * Compiles wheres to string to pass this string in query.
     *
     * @param  TwoElementsLogicExpression[]  $wheres
     */
    public function compileWheresComponent(Builder $builder, array $wheres): string
    {
        $result = $this->compileTwoElementLogicExpressions($wheres);

        return "WHERE {$result}";
    }
}
