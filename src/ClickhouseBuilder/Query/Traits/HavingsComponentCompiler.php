<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\TwoElementsLogicExpression;

trait HavingsComponentCompiler
{
    /**
     * Compiles havings to string to pass this string in query.
     *
     * @param  TwoElementsLogicExpression[]  $havings
     */
    public function compileHavingsComponent(Builder $builder, array $havings): string
    {
        $result = $this->compileTwoElementLogicExpressions($havings);

        return "HAVING {$result}";
    }
}
