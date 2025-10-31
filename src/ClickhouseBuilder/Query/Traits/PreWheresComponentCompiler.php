<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\TwoElementsLogicExpression;

trait PreWheresComponentCompiler
{
    /**
     * Compiles prewhere to string to pass this string in query.
     *
     * @param  TwoElementsLogicExpression[]  $preWheres
     */
    public function compilePrewheresComponent(Builder $builder, array $preWheres): string
    {
        $result = $this->compileTwoElementLogicExpressions($preWheres);

        return "PREWHERE {$result}";
    }
}
