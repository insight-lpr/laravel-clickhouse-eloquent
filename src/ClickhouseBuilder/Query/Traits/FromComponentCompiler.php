<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Traits;

use LaravelClickhouseEloquent\ClickhouseBuilder\Exceptions\GrammarException;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\From;

trait FromComponentCompiler
{
    /**
     * Compiles format statement.
     */
    public function compileFromComponent(BaseBuilder $builder, From $from): string
    {
        $this->verifyFrom($from);

        $table = $from->getTable();
        $alias = $from->getAlias();
        $final = $from->getFinal();

        $fromSection = '';
        $fromSection .= "FROM {$this->wrap($table)}";

        if (! is_null($alias)) {
            $fromSection .= " AS {$this->wrap($alias)}";
        }

        if (! is_null($final)) {
            $fromSection .= ' FINAL';
        }

        return $fromSection;
    }

    /**
     * Verifies from.
     *
     *
     * @throws GrammarException
     */
    private function verifyFrom(From $from)
    {
        if (is_null($from->getTable())) {
            throw GrammarException::wrongFrom();
        }
    }
}
