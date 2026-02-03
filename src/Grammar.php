<?php

namespace LaravelClickhouseEloquent;

class Grammar extends \Tinderbox\ClickhouseBuilder\Query\Grammar
{
    public function __construct()
    {
        // Prepend 'ctes' to beginning of select components
        array_unshift($this->selectComponents, 'ctes');
        // Add 'settings' to end of select components
        $this->selectComponents[] = 'settings';
    }

    /**
     * Compile the CTEs component of a SELECT query.
     *
     * @param Builder $builder
     * @param array $ctes
     * @return string
     */
    public function compileCTEsComponent(Builder $builder, array $ctes): string
    {
        if (empty($ctes)) {
            return '';
        }

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