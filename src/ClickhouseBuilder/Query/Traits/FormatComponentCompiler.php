<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;

trait FormatComponentCompiler
{
    /**
     * Compiles format to string to pass this string in query.
     */
    public function compileFormatComponent(Builder $builder, $format): string
    {
        return "FORMAT {$format}";
    }
}
