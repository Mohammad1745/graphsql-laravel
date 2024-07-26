<?php

namespace Bitsmind\Graphsql\Models;

use Illuminate\Database\Eloquent\Model;

class GraphSqlKey extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'key',
        'string'
    ];
}
