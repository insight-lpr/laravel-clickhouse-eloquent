<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query;

/**
 * Object to represent tuple.
 */
class Tuple
{
    /**
     * Tuple elements.
     *
     * @var array
     */
    private $elements = [];

    /**
     * Tuple constructor.
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * Get tuple elements.
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Add element to tuple.
     *
     * @param  array  ...$elements
     */
    public function addElements(...$elements): self
    {
        $this->elements = array_merge($this->elements, array_flatten($elements));

        return $this;
    }
}
