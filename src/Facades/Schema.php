<?php

namespace Bitsmind\GraphSql\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getSchema (string $modelPath = 'app/Models', string $search = '', bool $showHidden=false)
 *
 * @see \Bitsmind\GraphSql\Schema
 */
class Schema extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'graphsql-schema';
    }
}