<?php

namespace LaravelClickhouseEloquent\ClickhouseClient\Common;

use LaravelClickhouseEloquent\ClickhouseClient\Interfaces\FileInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Temporary table for select requests which receives data from local file.
 */
class TempTable implements FileInterface
{
    /**
     * Table name to use in query in where section.
     *
     * @var string
     */
    protected $name;

    /**
     * Table structure to map data in file on table.
     *
     * @var array
     */
    protected $structure = [];

    /**
     * Format.
     *
     * @var string
     */
    protected $format;

    /**
     * Source.
     *
     * @var string|FileInterface
     */
    protected $source;

    /**
     * TempTable constructor.
     *
     * @param  string|FileInterface  $source
     */
    public function __construct(string $name, $source, array $structure, string $format = Format::CSV)
    {
        $this->name = $name;
        $this->structure = $structure;
        $this->format = $format;

        $this->setSource($source);
    }

    protected function setSource($source)
    {
        if (is_scalar($source)) {
            $source = new File($source);
        }

        $this->source = $source;
    }

    /**
     * Returns table name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns table structure.
     */
    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * Returns format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    public function open(bool $gzip = true): StreamInterface
    {
        return $this->source->open($gzip);
    }
}
