<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums;

/**
 * Join types.
 */
enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case FULL = 'FULL';
    case CROSS = 'CROSS';
    case ASOF = 'ASOF';

    /**
     * Check if a value is a valid enum value.
     */
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
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
