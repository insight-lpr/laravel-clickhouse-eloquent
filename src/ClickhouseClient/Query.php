<?php

namespace LaravelClickhouseEloquent\ClickhouseClient;

/**
 * Query instance.
 */
class Query
{
    /**
     * SQL Query.
     *
     * @var string
     */
    protected $query;

    /**
     * Files attached to query.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Server to process query.
     *
     * @var \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    protected $server;

    /**
     * Query settings.
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Query constructor.
     */
    public function __construct(Server $server, string $query, array $files = [], array $settings = [])
    {
        $this->server = $server;
        $this->query = $query;
        $this->files = $files;
        $this->settings = $settings;
    }

    /**
     * Returns SQL query.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Returns files attached to query.
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Returns server to process query.
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Returns settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }
}
