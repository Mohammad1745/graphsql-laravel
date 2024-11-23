<?php

namespace Bitsmind\GraphSql\Facades;

use Illuminate\Support\Facades\Facade;

class Schema extends Facade
{
    /**
     * @method static array getSchema ($modelPath='app/Models')
     *
     * @see \Bitsmind\GraphSql\Schema
     */
    protected static function getFacadeAccessor(): string
    {
        return 'graphsql-schema';
    }
}