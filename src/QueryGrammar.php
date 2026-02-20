<?php

declare(strict_types=1);

namespace LaravelClickhouseEloquent;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class QueryGrammar extends Grammar
{
    public const PARAMETER_SIGN = '#@?';

    /** @inheritDoc */
    public function parameterize(array $values): string
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /** @inheritDoc */
    public function parameter($value)
    {
        return $this->isExpression($value) ? $this->getValue($value) : self::PARAMETER_SIGN;
    }

    /** @inheritDoc */
    public function compileWheres(Builder $query): string
    {
        return static::prepareParameters(parent::compileWheres($query));
    }

    /**
     * Second part of trick to change signs "?" to ":0", ":1" and so on
     * @param string $sql
     * @return string
     */
    public static function prepareParameters(string $sql): string
    {
        $parameterNum = 0;
        while (($pos = strpos($sql, self::PARAMETER_SIGN)) !== false) {
            $sql = substr_replace($sql, ":$parameterNum", $pos, strlen(self::PARAMETER_SIGN));
            $parameterNum++;
        }

        return $sql;
    }

    /** @inheritDoc */
    protected function compileDeleteWithoutJoins(Builder $query, $table, $where): string
    {
        return "alter table {$table} delete {$where}";
    }

    /**
     * Compile a select query into SQL with CTE support.
     *
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $query): string
    {
        $sql = parent::compileSelect($query);

        // Check for CTEs added via macro
        if (isset($query->clickhouseCtes) && !empty($query->clickhouseCtes)) {
            $sql = $this->compileCtes($query->clickhouseCtes) . $sql;
        }

        return $sql;
    }

    /**
     * Compile the CTEs into a WITH clause.
     *
     * @param array $ctes
     * @return string
     */
    protected function compileCtes(array $ctes): string
    {
        $parts = [];
        foreach ($ctes as $cte) {
            if ($cte['type'] === 'expression') {
                // Expression style: value AS name
                $parts[] = "{$cte['sql']} AS {$cte['name']}";
            } else {
                // Subquery style: name AS (SELECT ...)
                $parts[] = "{$cte['name']} AS ({$cte['sql']})";
            }
        }

        return 'WITH ' . implode(', ', $parts) . ' ';
    }

    /**
     * Convert the colon-style placeholders back to question marks before raw formatting.
     *
     * @param string $sql
     * @param array $bindings
     * @return string
     */
    public function substituteBindingsIntoRawSql($sql, $bindings)
    {
        $sql = preg_replace('/(?<!:):\d+/', '?', $sql);

        return parent::substituteBindingsIntoRawSql($sql, $bindings);
    }
}
