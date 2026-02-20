<?php

namespace LaravelClickhouseEloquent;

class Grammar extends \Tinderbox\ClickhouseBuilder\Query\Grammar
{
    public function __construct()
    {
        // Add 'settings' to end of select components
        $this->selectComponents[] = 'settings';
    }

    /**
     * Override compileSelect to place CTEs before the SELECT keyword.
     *
     * @param \Tinderbox\ClickhouseBuilder\Query\BaseBuilder $query
     * @return string
     */
    public function compileSelect(\Tinderbox\ClickhouseBuilder\Query\BaseBuilder $query)
    {
        $sql = parent::compileSelect($query);

        if ($query instanceof Builder && !empty($query->getCtes())) {
            $sql = $this->compileCtes($query->getCtes()) . ' ' . $sql;
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

        return 'WITH ' . implode(', ', $parts);
    }

    /**
     * Compile the SETTINGS component of a SELECT query.
     *
     * @param Builder $builder
     * @param array $settings
     * @return string
     */
    public function compileSettingsComponent($builder, array $settings): string
    {
        if (empty($settings)) {
            return '';
        }

        $strAr = [];
        foreach ($settings as $k => $v) {
            $strAr[] = is_int($v) ? "$k=$v" : "$k='$v'";
        }

        return 'SETTINGS ' . implode(', ', $strAr);
    }
}