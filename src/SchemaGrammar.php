<?php

declare(strict_types=1);

namespace LaravelClickhouseEloquent;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;

class SchemaGrammar extends Grammar
{
    /**
     * Compile the query to determine the list of tables.
     */
    public function compileTableExists(): string
    {
        return 'select * from system.tables where database = :0 and name = :1';
    }

    /**
     * Compile a create table command.
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $sql = 'CREATE TABLE :table (
                :columns
            )
            ENGINE = MergeTree()
            ORDER BY (:orderBy)';
        $orderBy = $blueprint->getAddedColumns()[0]->name;
        $bindings = [
            ':table' => $blueprint->getTable(),
            ':columns' => implode(",\n", $this->getColumns($blueprint)),
            ':orderBy' => $orderBy,
        ];
        $sql = str_replace(array_keys($bindings), array_values($bindings), $sql);

        return [$sql];
    }

    /**
     * Compile the blueprint's column definitions.
     */
    protected function getColumns(Blueprint $blueprint): array
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            $sql = $column->name.' '.$this->getType($column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the column definition for an integer type.
     */
    protected function typeInteger(Fluent $column): string
    {
        return 'Int32';
    }

    /**
     * Create the column definition for a big integer type.
     */
    protected function typeBigInteger(Fluent $column): string
    {
        return 'Int64';
    }

    /**
     * Create the column definition for a string type.
     */
    protected function typeString(Fluent $column): string
    {
        return 'String';
    }

    /**
     * Create the column definition for a timestamp type.
     */
    protected function typeTimestamp(Fluent $column): string
    {
        return 'DateTime';
    }

    /**
     * Create the column definition for a text type.
     */
    protected function typeText(Fluent $column): string
    {
        return 'String';
    }

    /**
     * Create the column definition for a long text type.
     */
    protected function typeLongText(Fluent $column): string
    {
        return 'String';
    }
}
