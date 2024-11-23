<?php

namespace Bitsmind\GraphSql\Facades;

use Illuminate\Support\Facades\Facade;

class QueryAssist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'query-assist';
    }
}