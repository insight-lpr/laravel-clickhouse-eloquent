<?php

namespace LaravelClickhouseEloquent;

use ClickHouseDB\Client;
use Illuminate\Support\Facades\DB;

trait WithClient
{
    public function getThisClient(): Client
    {
        return DB::connection($this->connection)->getClient();
    }

    /**
     * @deprecated use $this->getThisClient() instead
     */
    public static function getClient(): Client
    {
        return DB::connection((new static)->connection)->getClient();
    }
}
