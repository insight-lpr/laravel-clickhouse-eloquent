<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query;

use Closure;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\JoinStrict;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\JoinType;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Operator;
use LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\OrderDirection;
use LaravelClickhouseEloquent\ClickhouseClient\Common\File;
use LaravelClickhouseEloquent\ClickhouseClient\Common\FileFromString;
use LaravelClickhouseEloquent\ClickhouseClient\Common\TempTable;
use LaravelClickhouseEloquent\ClickhouseClient\Interfaces\FileInterface;

abstract class BaseBuilder
{
    /**
     * Columns for select.
     *
     * @var Column[]
     */
    protected $columns = [];

    /**
     * Table to select from.
     *
     * @var From|null
     */
    protected $from = null;

    /**
     * Sample expression.
     *
     * @var float|null
     */
    protected $sample;

    /**
     * Join clauses.
     *
     * @var JoinClause[]|null
     */
    protected $joins;

    /**
     * Array join clause.
     *
     * @var ArrayJoinClause
     */
    protected $arrayJoin;

    /**
     * Prewhere statements.
     *
     * @var TwoElementsLogicExpression[]
     */
    protected $prewheres = [];

    /**
     * Where statements.
     *
     * @var TwoElementsLogicExpression[]
     */
    protected $wheres = [];

    /**
     * Groupings.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Having statements.
     *
     * @var TwoElementsLogicExpression[]
     */
    protected $havings = [];

    /**
     * Order statements.
     *
     * @var array
     */
    protected $orders = [];

    /**
     * Limit.
     *
     * @var Limit|null
     */
    protected $limit;

    /**
     * Limit n by statement.
     *
     * @var Limit|null
     */
    protected $limitBy;

    /**
     * Queries to union.
     *
     * @var array
     */
    protected $unions = [];

    /**
     * Query format.
     *
     * @var Format|null
     */
    protected $format;

    /**
     * Grammar to build query parts.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * Queries which must be run asynchronous.
     *
     * @var array
     */
    protected $async = [];

    /**
     * Files which should be sent on server to store into temporary table.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Cluster name.
     *
     * @var string|null
     */
    protected $onCluster = null;

    /**
     * File representing values which should be inserted in table.
     *
     * @var FileInterface
     */
    protected $values;

    /**
     * Set columns for select statement.
     *
     * @param  array|mixed  $columns
     * @return static
     */
    public function select(...$columns)
    {
        $columns = isset($columns[0]) && is_array($columns[0]) ? $columns[0] : $columns;

        if (empty($columns)) {
            $columns[] = '*';
        }

        $this->columns = $this->processColumns($columns);

        return $this;
    }

    /**
     * Returns query for count total rows without limit.
     *
     * @return static
     */
    public function getCountQuery()
    {
        $without = ['columns' => [], 'limit' => null];

        if (empty($this->groups)) {
            $without['orders'] = [];
        }

        return $this->cloneWithout($without)->select(raw('count() as `count`'));
    }

    /**
     * Clone the query without the given properties.
     *
     *
     * @return static
     */
    public function cloneWithout(array $except)
    {
        return tp(
            clone $this,
            function ($clone) use ($except) {
                foreach ($except as $property => $value) {
                    $clone->{$property} = $value;
                }
            }
        );
    }

    /**
     * Add columns to exist select statement.
     *
     * @param  array|mixed  $columns
     * @return static
     */
    public function addSelect(...$columns)
    {
        $columns = isset($columns[0]) && is_array($columns[0]) ? $columns[0] : $columns;

        if (empty($columns)) {
            $columns[] = '*';
        }

        $this->columns = array_merge($this->columns, $this->processColumns($columns));

        return $this;
    }

    /**
     * A factory method for Column.
     */
    protected function makeColumn(): Column
    {
        return new Column($this);
    }

    /**
     * Prepares columns given by user to Column objects.
     */
    protected function processColumns(array $columns, bool $withAliases = true): array
    {
        $result = [];

        foreach ($columns as $column => $value) {
            if ($value instanceof Closure) {
                $columnName = $column;
                $column = $this->makeColumn();

                if (! is_int($columnName)) {
                    $column->name($columnName);
                }

                $column = tp($column, $value);

                if ($column->getSubQuery()) {
                    $column->query($column->getSubQuery());
                }
            }

            if ($value instanceof BaseBuilder) {
                $alias = is_string($column) ? $column : null;
                $column = $this->makeColumn()->query($value);

                if (! is_null($alias) && $withAliases) {
                    $column->as($alias);
                }
            }

            if (is_int($column)) {
                $column = $value;
                $value = null;
            }

            if (! $column instanceof Column) {
                $alias = is_string($value) ? $value : null;

                $column = $this->makeColumn()->name($column);

                if (! is_null($alias) && $withAliases) {
                    $column->as($alias);
                }
            }

            $result[] = $column;
        }

        return $result;
    }

    /**
     * Sets table to from statement.
     *
     * @param  Closure|Builder|string  $table
     * @return static
     */
    public function from($table, ?string $alias = null, ?bool $isFinal = null)
    {
        $this->from = new From($this);

        /*
         * If builder instance given, then we assume that from section should contain sub-query
         */
        if ($table instanceof BaseBuilder) {
            $this->from->query($table);

            $this->files = array_merge($this->files, $table->getFiles());
        }

        /*
         * If closure given, then we call it and pass From object as argument to
         * set up From object in callback
         */
        if ($table instanceof Closure) {
            $table($this->from);
        }

        /*
         * If given anything that is not builder instance or callback. For example, string,
         * then we assume that table name was given.
         */
        if (! $table instanceof Closure && ! $table instanceof BaseBuilder) {
            $this->from->table($table);
        }

        if (! is_null($alias)) {
            $this->from->as($alias);
        }

        if (! is_null($isFinal)) {
            $this->from->final($isFinal);
        }

        /*
         * If subQuery method was executed on From object, then we take subQuery and "execute" it
         */
        if (! is_null($this->from->getSubQuery())) {
            $this->from->query($this->from->getSubQuery());
        }

        return $this;
    }

    /**
     * Alias for from method.
     *
     *
     * @return static
     */
    public function table($table, ?string $alias = null, ?bool $isFinal = null)
    {
        return $this->from($table, $alias, $isFinal);
    }

    /**
     * Set sample expression.
     *
     *
     * @return static
     */
    public function sample(float $coefficient)
    {
        $this->sample = $coefficient;

        return $this;
    }

    /**
     * Add queries to union with.
     *
     * @param  self|Closure  $query
     * @return static
     */
    public function unionAll($query)
    {
        if ($query instanceof Closure) {
            $query = tp($this->newQuery(), $query);
        }

        if ($query instanceof BaseBuilder) {
            $this->unions[] = $query;
        } else {
            throw new \InvalidArgumentException('Argument for unionAll must be closure or builder instance.');
        }

        return $this;
    }

    /**
     * Set alias for table in from statement.
     *
     *
     * @return static
     */
    public function as(string $alias)
    {
        $this->from->as($alias);

        return $this;
    }

    /**
     * As method alias.
     *
     *
     * @return static
     */
    public function alias(string $alias)
    {
        return $this->as($alias);
    }

    /**
     * Sets final option on from statement.
     *
     *
     * @return static
     */
    public function final(bool $final = true)
    {
        $this->from->final($final);

        return $this;
    }

    /**
     * Sets on cluster option for query.
     *
     *
     * @return static
     */
    public function onCluster(string $clusterName)
    {
        $this->onCluster = $clusterName;

        return $this;
    }

    /**
     * Add array join to query.
     *
     * @param  string|Expression  $arrayIdentifier
     * @return static
     */
    public function arrayJoin($arrayIdentifier)
    {
        $this->arrayJoin = new ArrayJoinClause($this);
        $this->arrayJoin->array($arrayIdentifier);

        return $this;
    }

    /**
     * Add left array join to query.
     *
     * @param  string|Expression  $arrayIdentifier
     * @return static
     */
    public function leftArrayJoin($arrayIdentifier)
    {
        $this->arrayJoin = new ArrayJoinClause($this);
        $this->arrayJoin->left()->array($arrayIdentifier);

        return $this;
    }

    /**
     * Add join to query.
     *
     * @param  string|self|Closure  $table  Table to select from, also may be a sub-query
     * @param  string|null  $strict  All or any
     * @param  string|null  $type  Left or inner
     * @param  array|null  $using  Columns to use for join
     * @param  bool  $global  Global distribution for right table
     * @param  string|null  $alias  Alias of joined table or sub-query
     * @return static
     */
    public function join(
        $table,
        ?string $strict = null,
        ?string $type = null,
        ?array $using = null,
        bool $global = false,
        ?string $alias = null
    ) {
        $join = new JoinClause($this);

        /*
         * If builder instance given, then we assume that sub-query should be used as table in join
         */
        if ($table instanceof BaseBuilder) {
            $join->query($table);

            $this->files = array_merge($this->files, $table->getFiles());
        }

        /*
         * If closure given, then we call it and pass From object as argument to
         * set up JoinClause object in callback
         */
        if ($table instanceof Closure) {
            $table($join);
        }

        /*
         * If given anything that is not builder instance or callback. For example, string,
         * then we assume that table name was given.
         */
        if (! $table instanceof Closure && ! $table instanceof BaseBuilder) {
            $join->table($table);
        }

        /*
         * If using was given, then merge it with using given before, in closure
         */
        if (! is_null($using)) {
            $join->addUsing($using);
        }

        if (! is_null($strict) && is_null($join->getStrict())) {
            $join->strict($strict);
        }

        if (! is_null($type) && is_null($join->getType())) {
            $join->type($type);
        }

        if (! is_null($alias) && is_null($join->getAlias())) {
            $join->as($alias);
        }

        $join->distributed($global);

        if (! is_null($join->getSubQuery())) {
            $join->query($join->getSubQuery());
        }

        $this->joins[] = $join;

        return $this;
    }

    /**
     * Left join.
     *
     * Alias for join method, but without specified strictness
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function leftJoin($table, ?string $strict = null, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, $strict ?? JoinStrict::ALL->value, JoinType::LEFT->value, $using, $global, $alias);
    }

    /**
     * Inner join.
     *
     * Alias for join method, but without specified strictness
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function innerJoin($table, ?string $strict = null, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, $strict ?? JoinStrict::ALL->value, JoinType::INNER->value, $using, $global, $alias);
    }

    /**
     * Any left join.
     *
     * Alias for join method, but with specified any strictness
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function anyLeftJoin($table, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, JoinStrict::ANY->value, JoinType::LEFT->value, $using, $global, $alias);
    }

    /**
     * All left join.
     *
     * Alias for join method, but with specified all strictness.
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function allLeftJoin($table, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, JoinStrict::ALL->value, JoinType::LEFT->value, $using, $global, $alias);
    }

    /**
     * Any inner join.
     *
     * Alias for join method, but with specified any strictness.
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function anyInnerJoin($table, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, JoinStrict::ANY->value, JoinType::INNER->value, $using, $global, $alias);
    }

    /**
     * All inner join.
     *
     * Alias for join method, but with specified all strictness.
     *
     * @param  string|self|Closure  $table
     * @return static
     */
    public function allInnerJoin($table, ?array $using = null, bool $global = false, ?string $alias = null)
    {
        return $this->join($table, JoinStrict::ALL->value, JoinType::INNER->value, $using, $global, $alias);
    }

    /**
     * Get two elements logic expression to put it in the right place.
     *
     *
     * Used in where, prewhere and having methods.
     *
     * @param  TwoElementsLogicExpression|string|Closure|self  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     */
    protected function assembleTwoElementsLogicExpression(
        $column,
        $operator,
        $value,
        string $concatOperator,
        string $section
    ): TwoElementsLogicExpression {
        $expression = new TwoElementsLogicExpression($this);

        /*
         * If user passed TwoElementsLogicExpression as first argument, then we assume that user has set up himself.
         */
        if ($column instanceof TwoElementsLogicExpression && is_null($value)) {
            return $column;
        }

        if ($column instanceof TwoElementsLogicExpression && $value instanceof TwoElementsLogicExpression) {
            $expression->firstElement($column);
            $expression->secondElement($value);
            $expression->operator($operator);
            $expression->concatOperator($concatOperator);

            return $expression;
        }

        /*
         * If closure, then we pass fresh query builder inside and based on their state after evaluating try to assume
         * what user expects to perform.
         * If resulting query builder have elements corresponding to requested section, then we assume that user wanted
         * to just wrap this in parenthesis, otherwise - subquery.
         */
        if ($column instanceof Closure) {
            $query = tp($this->newQuery(), $column);

            if (is_null($query->getFrom()) && empty($query->getColumns())) {
                $expression->firstElement($query->{"get{$section}"}());
            } else {
                $expression->firstElement(new Expression("({$query->toSql()})"));
            }
        }

        /*
         * If as column was passed builder instance, than we perform subquery in first element position.
         */
        if ($column instanceof BaseBuilder) {
            $expression->firstElementQuery($column);
        }

        /*
         * If builder instance given as value, then we assume that sub-query should be used there.
         */
        if ($value instanceof BaseBuilder || $value instanceof Closure) {
            $expression->secondElementQuery($value);
        }

        /*
         * Set up other parameters if none of them was set up before in TwoElementsLogicExpression object
         */
        if (is_null($expression->getFirstElement()) && ! is_null($column)) {
            $expression->firstElement(is_string($column) ? new Identifier($column) : $column);
        }

        if (is_null($expression->getSecondElement()) && ! is_null($value)) {
            if (is_array($value) && count($value) === 2 && Operator::isValid($operator) && in_array(
                $operator,
                [Operator::BETWEEN->value, Operator::NOT_BETWEEN->value]
            )
            ) {
                $value = (new TwoElementsLogicExpression($this))
                    ->firstElement($value[0])
                    ->operator(Operator::AND->value)
                    ->secondElement($value[1])
                    ->concatOperator($concatOperator);
            }

            if (is_array($value) && Operator::isValid($operator) && in_array(
                $operator,
                [Operator::IN->value, Operator::NOT_IN->value]
            )
            ) {
                $value = new Tuple($value);
            }

            $expression->secondElement($value);
        }

        $expression->concatOperator($concatOperator);

        if (is_string($operator)) {
            $expression->operator($operator);
        }

        return $expression;
    }

    /**
     * Prepare operator for where and prewhere statement.
     *
     * @param  mixed  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     */
    protected function prepareValueAndOperator($value, $operator, $useDefault = false): array
    {
        if ($useDefault) {
            $value = $operator;

            if (is_array($value)) {
                $operator = Operator::IN->value;
            } else {
                $operator = Operator::EQUALS->value;
            }

            return [$value, $operator];
        }

        return [$value, $operator];
    }

    /**
     * Add prewhere statement.
     *
     * @param  TwoElementsLogicExpression|self|Closure|string  $column
     * @param  mixed  $operator
     * @param  TwoElementsLogicExpression|self|Closure|string|null  $value
     * @return static
     */
    public function preWhere($column, $operator = null, $value = null, string $concatOperator = Operator::AND->value)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        $this->prewheres[] = $this->assembleTwoElementsLogicExpression(
            $column,
            $operator,
            $value,
            $concatOperator,
            'prewheres'
        );

        return $this;
    }

    /**
     * Add prewhere statement "as is".
     *
     *
     * @return static
     */
    public function preWhereRaw(string $expression)
    {
        return $this->preWhere(new Expression($expression));
    }

    /**
     * Add prewhere statement "as is", but with OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereRaw(string $expression)
    {
        return $this->preWhere(new Expression($expression), null, null, Operator::OR->value);
    }

    /**
     * Add prewhere statement but with OR operator.
     *
     * @param  null  $operator
     * @param  null  $value
     * @return static
     */
    public function orPreWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        return $this->prewhere($column, $operator, $value, Operator::OR->value);
    }

    /**
     * Add prewhere statement with IN operator.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function preWhereIn($column, $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_IN->value : Operator::IN->value;

        if (is_array($values)) {
            $values = new Tuple($values);
        } elseif (is_string($values) && isset($this->files[$values])) {
            $values = new Identifier($values);
        }

        return $this->preWhere($column, $type, $values, $boolean);
    }

    /**
     * Add prewhere statement with IN operator and OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereIn($column, $values)
    {
        return $this->preWhereIn($column, $values, Operator::OR->value);
    }

    /**
     * Add prewhere statement with NOT IN operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function preWhereNotIn($column, $values, $boolean = Operator::AND->value)
    {
        return $this->preWhereIn($column, $values, $boolean, true);
    }

    /**
     * Add prewhere statement with NOT IN operator and OR operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function orPreWhereNotIn($column, $values, $boolean = Operator::OR->value)
    {
        return $this->preWhereNotIn($column, $values, $boolean);
    }

    /**
     * Add prewhere statement with BETWEEN simulation.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function preWhereBetween($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->preWhere($column, $type, [$values[0], $values[1]], $boolean);
    }

    /**
     * Add prewhere statement with BETWEEN simulation, but with column names as value.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function preWhereBetweenColumns($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->preWhere($column, $type, [new Identifier($values[0]), new Identifier($values[1])], $boolean);
    }

    /**
     * Add prewhere statement with NOT BETWEEN simulation, but with column names as value.
     *
     * @param  string  $boolean
     * @return static
     */
    public function preWhereNotBetweenColumns($column, array $values, $boolean = Operator::AND->value)
    {
        return $this->preWhere(
            $column,
            Operator::NOT_BETWEEN->value,
            [new Identifier($values[0]), new Identifier($values[1])],
            $boolean
        );
    }

    /**
     * Add prewhere statement with BETWEEN simulation, but with column names as value and OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereBetweenColumns($column, array $values)
    {
        return $this->preWhereBetweenColumns($column, $values, Operator::OR->value);
    }

    /**
     * Add prewhere statement with NOT BETWEEN simulation, but with column names as value and OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereNotBetweenColumns($column, array $values)
    {
        return $this->preWhereNotBetweenColumns($column, $values, Operator::OR->value);
    }

    /**
     * Add prewhere statement with BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereBetween($column, array $values)
    {
        return $this->preWhereBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add prewhere statement with NOT BETWEEN simulation.
     *
     * @param  string  $boolean
     * @return static
     */
    public function preWhereNotBetween($column, array $values, $boolean = Operator::AND->value)
    {
        return $this->preWhereBetween($column, $values, $boolean, true);
    }

    /**
     * Add prewhere statement with NOT BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orPreWhereNotBetween($column, array $values)
    {
        return $this->preWhereNotBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add where statement.
     *
     * @param  TwoElementsLogicExpression|string|Closure|self  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where($column, $operator = null, $value = null, string $concatOperator = Operator::AND->value)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        $this->wheres[] = $this->assembleTwoElementsLogicExpression(
            $column,
            $operator,
            $value,
            $concatOperator,
            'wheres'
        );

        return $this;
    }

    /**
     * Add where statement "as is".
     *
     *
     * @return static
     */
    public function whereRaw(string $expression)
    {
        return $this->where(new Expression($expression));
    }

    /**
     * Add where statement "as is" with OR operator.
     *
     *
     * @return static
     */
    public function orWhereRaw(string $expression)
    {
        return $this->where(new Expression($expression), null, null, Operator::OR->value);
    }

    /**
     * Add where statement with OR operator.
     *
     * @param  null  $operator
     * @param  null  $value
     * @return static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        return $this->where($column, $operator, $value, Operator::OR->value);
    }

    /**
     * Add where statement with IN operator.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereIn($column, $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_IN->value : Operator::IN->value;

        if (is_array($values)) {
            if (empty($values)) {
                return $type === Operator::IN->value ? $this->where(new Expression('0 = 1')) : $this;
            }

            $values = new Tuple($values);
        } elseif (is_string($values) && isset($this->files[$values])) {
            $values = new Identifier($values);
        }

        return $this->where($column, $type, $values, $boolean);
    }

    /**
     * Add where statement with GLOBAL option and IN operator.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereGlobalIn($column, $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::GLOBAL_NOT_IN->value : Operator::GLOBAL_IN->value;

        if (is_array($values)) {
            $values = new Tuple($values);
        } elseif (is_string($values) && isset($this->files[$values])) {
            $values = new Identifier($values);
        }

        return $this->where($column, $type, $values, $boolean);
    }

    /**
     * Add where statement with GLOBAL option and IN operator and OR operator.
     *
     *
     * @return static
     */
    public function orWhereGlobalIn($column, $values)
    {
        return $this->whereGlobalIn($column, $values, Operator::OR->value);
    }

    /**
     * Add where statement with GLOBAL option and NOT IN operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function whereGlobalNotIn($column, $values, $boolean = Operator::AND->value)
    {
        return $this->whereGlobalIn($column, $values, $boolean, true);
    }

    /**
     * Add where statement with GLOBAL option and NOT IN operator and OR operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function orWhereGlobalNotIn($column, $values, $boolean = Operator::OR->value)
    {
        return $this->whereGlobalNotIn($column, $values, $boolean);
    }

    /**
     * Add where statement with IN operator and OR operator.
     *
     *
     * @return static
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, Operator::OR->value);
    }

    /**
     * Add where statement with NOT IN operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function whereNotIn($column, $values, $boolean = Operator::AND->value)
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add where statement with NOT IN operator and OR operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function orWhereNotIn($column, $values, $boolean = Operator::OR->value)
    {
        return $this->whereNotIn($column, $values, $boolean);
    }

    /**
     * Add where statement with BETWEEN simulation.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereBetween($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $operator = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->where($column, $operator, [$values[0], $values[1]], $boolean);
    }

    /**
     * Add where statement with BETWEEN simulation, but with column names as value.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function whereBetweenColumns($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->where($column, $type, [new Identifier($values[0]), new Identifier($values[1])], $boolean);
    }

    /**
     * Add where statement with BETWEEN simulation, but with column names as value and OR operator.
     *
     *
     * @return static
     */
    public function orWhereBetweenColumns($column, array $values)
    {
        return $this->whereBetweenColumns($column, $values, Operator::OR->value);
    }

    /**
     * Add where statement with BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orWhereBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add where statement with NOT BETWEEN simulation.
     *
     * @param  string  $boolean
     * @return static
     */
    public function whereNotBetween($column, array $values, $boolean = Operator::AND->value)
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add prewhere statement with NOT BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orWhereNotBetween($column, array $values)
    {
        return $this->whereNotBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add having statement.
     *
     * @param  TwoElementsLogicExpression|string|Closure|self  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function having($column, $operator = null, $value = null, string $concatOperator = Operator::AND->value)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        $this->havings[] = $this->assembleTwoElementsLogicExpression(
            $column,
            $operator,
            $value,
            $concatOperator,
            'havings'
        );

        return $this;
    }

    /**
     * Add having statement "as is".
     *
     *
     * @return static
     */
    public function havingRaw(string $expression)
    {
        return $this->having(new Expression($expression));
    }

    /**
     * Add having statement "as is" with OR operator.
     *
     *
     * @return static
     */
    public function orHavingRaw(string $expression)
    {
        return $this->having(new Expression($expression), null, null, Operator::OR->value);
    }

    /**
     * Add having statement with OR operator.
     *
     * @param  null  $operator
     * @param  null  $value
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 2);

        return $this->having($column, $operator, $value, Operator::OR->value);
    }

    /**
     * Add having statement with IN operator.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function havingIn($column, $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_IN->value : Operator::IN->value;

        if (is_array($values)) {
            $values = new Tuple($values);
        } elseif (is_string($values) && isset($this->files[$values])) {
            $values = new Identifier($values);
        }

        return $this->having($column, $type, $values, $boolean);
    }

    /**
     * Add having statement with IN operator and OR operator.
     *
     *
     * @return static
     */
    public function orHavingIn($column, $values)
    {
        return $this->havingIn($column, $values, Operator::OR->value);
    }

    /**
     * Add having statement with NOT IN operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function havingNotIn($column, $values, $boolean = Operator::AND->value)
    {
        return $this->havingIn($column, $values, $boolean, true);
    }

    /**
     * Add having statement with NOT IN operator and OR operator.
     *
     * @param  string  $boolean
     * @return static
     */
    public function orHavingNotIn($column, $values, $boolean = Operator::OR->value)
    {
        return $this->havingNotIn($column, $values, $boolean);
    }

    /**
     * Add having statement with BETWEEN simulation.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function havingBetween($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $operator = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->having($column, $operator, [$values[0], $values[1]], $boolean);
    }

    /**
     * Add having statement with BETWEEN simulation, but with column names as value.
     *
     * @param  string  $boolean
     * @param  bool  $not
     * @return static
     */
    public function havingBetweenColumns($column, array $values, $boolean = Operator::AND->value, $not = false)
    {
        $type = $not ? Operator::NOT_BETWEEN->value : Operator::BETWEEN->value;

        return $this->having($column, $type, [new Identifier($values[0]), new Identifier($values[1])], $boolean);
    }

    /**
     * Add having statement with BETWEEN simulation, but with column names as value and OR operator.
     *
     *
     * @return static
     */
    public function orHavingBetweenColumns($column, array $values)
    {
        return $this->havingBetweenColumns($column, $values, Operator::OR->value);
    }

    /**
     * Add having statement with BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orHavingBetween($column, array $values)
    {
        return $this->havingBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add having statement with NOT BETWEEN simulation.
     *
     * @param  string  $boolean
     * @return static
     */
    public function havingNotBetween($column, array $values, $boolean = Operator::AND->value)
    {
        return $this->havingBetween($column, $values, $boolean, true);
    }

    /**
     * Add having statement with NOT BETWEEN simulation and OR operator.
     *
     *
     * @return static
     */
    public function orHavingNotBetween($column, array $values)
    {
        return $this->havingNotBetween($column, $values, Operator::OR->value);
    }

    /**
     * Add dictionary value to select statement.
     *
     * @param  array|string  $key
     * @return static
     */
    public function addSelectDict(string $dict, string $attribute, $key, ?string $as = null)
    {
        if (is_null($as)) {
            $as = $attribute;
        }

        $id = is_array($key) ? 'tuple('.implode(
            ', ',
            array_map([$this->grammar, 'wrap'], $key)
        ).')' : $this->grammar->wrap($key);

        return $this
            ->addSelect(new Expression("dictGetString('{$dict}', '{$attribute}', {$id}) as `{$as}`"));
    }

    /**
     * Add where on dictionary value in where statement.
     *
     * @param  array|string  $key
     * @return static
     */
    public function whereDict(
        string $dict,
        string $attribute,
        $key,
        $operator = null,
        $value = null,
        string $concatOperator = Operator::AND->value
    ) {
        $this->addSelectDict($dict, $attribute, $key);

        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 4);

        return $this->where($attribute, $operator, $value, $concatOperator);
    }

    /**
     * Add where on dictionary value in where statement and OR operator.
     *
     *
     * @return static
     */
    public function orWhereDict(
        string $dict,
        string $attribute,
        $key,
        $operator = null,
        $value = null
    ) {
        [$value, $operator] = $this->prepareValueAndOperator($value, $operator, func_num_args() == 4);

        return $this->whereDict($dict, $attribute, $key, $operator, $value, Operator::OR->value);
    }

    /**
     * Add request which must be runned asynchronous.
     *
     * @param  Closure|self|null  $asyncQueries
     * @return static
     */
    public function asyncWithQuery($asyncQueries = null)
    {
        if (is_null($asyncQueries)) {
            return $this->async[] = $this->newQuery();
        }

        if ($asyncQueries instanceof Closure) {
            $asyncQueries = tp($this->newQuery(), $asyncQueries);
        }

        if ($asyncQueries instanceof BaseBuilder) {
            $this->async[] = $asyncQueries;
        } else {
            throw new \InvalidArgumentException('Argument for async method must be Closure, Builder or nothing');
        }

        return $this;
    }

    /**
     * Add limit statement.
     *
     *
     * @return static
     */
    public function limit(int $limit, ?int $offset = null)
    {
        $this->limit = new Limit($limit, $offset);

        return $this;
    }

    /**
     * Add limit n by statement.
     *
     * @param  array  ...$columns
     * @return static
     */
    public function limitBy(int $count, ...$columns)
    {
        $columns = isset($columns[0]) && is_array($columns[0]) ? $columns[0] : $columns;

        $this->limitBy = new Limit($count, null, $this->processColumns($columns, false));

        return $this;
    }

    /**
     * Alias for limit method.
     *
     *
     * @return static
     */
    public function take(int $limit, ?int $offset = null)
    {
        return $this->limit($limit, $offset);
    }

    /**
     * Alias for limitBy method.
     *
     * @param  array  ...$columns
     * @return static
     */
    public function takeBy(int $count, ...$columns)
    {
        return $this->limitBy($count, ...$columns);
    }

    /**
     * Add group by statement.
     *
     *
     * @return static
     */
    public function groupBy(...$columns)
    {
        $columns = isset($columns[0]) && is_array($columns[0]) ? $columns[0] : $columns;

        $this->groups = $this->processColumns($columns, false);

        return $this;
    }

    /**
     * Add group by statement to exist group statements.
     *
     *
     * @return static
     */
    public function addGroupBy(...$columns)
    {
        $columns = isset($columns[0]) && is_array($columns[0]) ? $columns[0] : $columns;

        $this->groups = array_merge($this->groups, $this->processColumns($columns, false));

        return $this;
    }

    /**
     * Add order by statement.
     *
     * @param  string|Closure  $column
     * @return static
     */
    public function orderBy($column, string $direction = 'asc', ?string $collate = null)
    {
        $column = $this->processColumns([$column], false)[0];

        $direction = new OrderDirection(strtoupper($direction));

        $this->orders[] = [$column, $direction, $collate];

        return $this;
    }

    /**
     * Add order by statement "as is".
     *
     *
     * @return static
     */
    public function orderByRaw(string $expression)
    {
        $column = $this->processColumns([new Expression($expression)], false)[0];
        $this->orders[] = [$column, null, null];

        return $this;
    }

    /**
     * Add ASC order statement.
     *
     *
     * @return static
     */
    public function orderByAsc($column, ?string $collate = null)
    {
        return $this->orderBy($column, OrderDirection::ASC->value, $collate);
    }

    /**
     * Add DESC order statement.
     *
     *
     * @return static
     */
    public function orderByDesc($column, ?string $collate = null)
    {
        return $this->orderBy($column, OrderDirection::DESC->value, $collate);
    }

    /**
     * Set query result format.
     *
     *
     * @return static
     */
    public function format(string $format)
    {
        $this->format = new Format(strtoupper($format));

        return $this;
    }

    /**
     * Get the SQL representation of the query.
     */
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Get an array of the SQL queries from all added async builders.
     */
    public function toAsyncSqls(): array
    {
        return array_map(
            function ($query) {
                /** @var self $query */
                return ['query' => $query->toSql(), 'files' => $query->getFiles()];
            },
            $this->getAsyncQueries()
        );
    }

    /**
     * Get an array of the SQL queries from all added async builders.
     */
    public function toAsyncQueries(): array
    {
        return array_map(
            function ($query) {
                /** @var self $query */
                return $query->toQuery();
            },
            $this->getAsyncQueries()
        );
    }

    /**
     * Get columns for select statement.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get order statements.
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * Get group statements.
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get having statements.
     */
    public function getHavings(): array
    {
        return $this->havings;
    }

    /**
     * Get prewhere statements.
     */
    public function getPreWheres(): array
    {
        return $this->prewheres;
    }

    /**
     * Get where statements.
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Get cluster name.
     */
    public function getOnCluster(): ?string
    {
        return $this->onCluster;
    }

    /**
     * Get From object.
     */
    public function getFrom(): ?From
    {
        return $this->from;
    }

    /**
     * Get ArrayJoinClause.
     */
    public function getArrayJoin(): ?ArrayJoinClause
    {
        return $this->arrayJoin;
    }

    /**
     * Get JoinClause.
     *
     * @return JoinClause[]|null
     */
    public function getJoins(): ?array
    {
        return $this->joins;
    }

    /**
     * Get limit statement.
     */
    public function getLimit(): ?Limit
    {
        return $this->limit;
    }

    /**
     * Get limit by statement.
     */
    public function getLimitBy(): ?Limit
    {
        return $this->limitBy;
    }

    /**
     * Get sample statement.
     */
    public function getSample(): ?float
    {
        return $this->sample;
    }

    /**
     * Get query unions.
     */
    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * Get format.
     */
    public function getFormat(): ?Format
    {
        return $this->format;
    }

    /**
     * Add file with data to query.
     *
     *
     * @return $this
     */
    public function addFile(TempTable $file)
    {
        $this->files[$file->getName()] = $file;

        return $this;
    }

    public function values($values)
    {
        $this->values = $this->prepareFile($values);
    }

    public function getValues(): FileInterface
    {
        return $this->values;
    }

    /**
     * Returns files which should be sent on server.
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Gather all builders from builder. Including nested in async builders.
     */
    public function getAsyncQueries(): array
    {
        $result = [];

        foreach ($this->async as $query) {
            $result = array_merge($query->getAsyncQueries(), $result);
        }

        return array_merge([$this], $result);
    }

    /**
     * Prepares file.
     *
     *
     * @return File|FileFromString
     */
    protected function prepareFile($file): FileInterface
    {
        $file = file_from($file);

        return $file;
    }
}
