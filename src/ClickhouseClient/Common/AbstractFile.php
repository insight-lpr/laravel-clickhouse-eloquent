<?php

namespace LaravelClickhouseEloquent\ClickhouseClient\Common;

abstract class AbstractFile
{
    /**
     * Source file.
     *
     * @var string
     */
    protected $source;

    /**
     * File constructor.
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * Returns full path to source file.
     */
    public function getSource(): string
    {
        return $this->source;
    }
}
