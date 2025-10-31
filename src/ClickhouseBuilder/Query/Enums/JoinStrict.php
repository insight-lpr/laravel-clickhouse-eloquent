<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums;

/**
 * Join strictness.
 */
enum JoinStrict: string
{
    case ALL = 'ALL';
    case ANY = 'ANY';

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
