<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder as Builder;

trait SampleComponentCompiler
{
    /**
     * Compiles sample to string to pass this string in query.
     *
     * @param Builder    $builder
     * @param float|null $sample
     *
     * @return string
     */
    public function compileSampleComponent(Builder $builder, float $sample = null): string
    {
        return "SAMPLE {$sample}";
    }
}
