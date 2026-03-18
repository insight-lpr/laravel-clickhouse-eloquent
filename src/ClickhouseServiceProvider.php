<?php

declare(strict_types=1);

namespace LaravelClickhouseEloquent;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider to connect Clickhouse driver in Laravel.
 */
class ClickhouseServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $db = $this->app->make('db');

        $db->extend('clickhouse', function ($config, $name) {
            $config['name'] = $name;

            return Connection::createWithClient($config);
        });

        BaseModel::setEventDispatcher($this->app['events']);

        $this->registerQueryBuilderMacros();
    }

    protected function registerQueryBuilderMacros(): void
    {
        QueryBuilder::macro('withCte', function (string $name, \Closure|QueryBuilder|string $query): QueryBuilder {
            /** @var QueryBuilder $this */
            if ($query instanceof \Closure) {
                $inner = $this->newQuery();
                $query($inner);
                $sql = $inner->toSql();
                $bindings = $inner->getBindings();
            } elseif ($query instanceof QueryBuilder) {
                $sql = $query->toSql();
                $bindings = $query->getBindings();
            } else {
                $sql = $query;
                $bindings = [];
            }

            CteRegistry::addCte($this, [
                'name' => $name,
                'type' => 'subquery',
                'sql' => $sql,
            ], $bindings);

            if (!empty($bindings)) {
                $this->addBinding($bindings, 'where');
            }

            return $this;
        });

        QueryBuilder::macro('withCteExpression', function (string $name, mixed $value): QueryBuilder {
            /** @var QueryBuilder $this */
            $sql = match (true) {
                $value instanceof \Closure => '(' . (function () use ($value) {
                    $inner = $this->newQuery();
                    $value($inner);
                    return $inner->toSql();
                })() . ')',
                $value instanceof QueryBuilder => '(' . $value->toSql() . ')',
                is_string($value) => "'" . addslashes($value) . "'",
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            };

            CteRegistry::addCte($this, [
                'name' => $name,
                'type' => 'expression',
                'sql' => $sql,
            ]);

            return $this;
        });
    }
}
