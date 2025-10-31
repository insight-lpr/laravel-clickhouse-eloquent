<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query;

/**
 * Object for element identity which should be escaped.
 */
class Identifier
{
    /**
     * Value.
     *
     * @var mixed
     */
    private $value;

    /**
     * Identifier constructor.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Converts value to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
