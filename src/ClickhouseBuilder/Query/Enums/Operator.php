<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums;

/**
 * Operators.
 */
enum Operator: string
{
    case EQUALS = '=';
    case NOT_EQUALS = '!=';
    case LESS_OR_EQUALS = '<=';
    case GREATER_OR_EQUALS = '>=';
    case LESS = '<';
    case GREATER = '>';
    case LIKE = 'LIKE';
    case ILIKE = 'ILIKE';
    case NOT_LIKE = 'NOT LIKE';
    case BETWEEN = 'BETWEEN';
    case NOT_BETWEEN = 'NOT BETWEEN';
    case IN = 'IN';
    case NOT_IN = 'NOT IN';
    case GLOBAL_IN = 'GLOBAL IN';
    case GLOBAL_NOT_IN = 'GLOBAL NOT IN';
    case AND = 'AND';
    case OR = 'OR';
    case CONCAT = '||';
    case LAMBDA = '->';
    case DIVIDE = '/';
    case MODULO = '%';
    case MULTIPLE = '*';
    case PLUS = '+';
    case MINUS = '-';

    /**
     * Check if a value is a valid enum value.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return self::tryFrom($value) !== null;
    }

    /**
     * Get the enum value as a string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the enum case name.
     */
    public function getKey(): string
    {
        return $this->name;
    }
}
