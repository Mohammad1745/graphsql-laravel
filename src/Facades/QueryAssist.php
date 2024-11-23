<?php

namespace Bitsmind\GraphSql\Facades;

use Illuminate\Support\Facades\Facade;

class QueryAssist extends Facade
{
    /**
     * @method static mixed queryPagination ($dbQuery, array &$query, int $defaultPage = 1, int $defaultLength = 100)
     * @method static mixed queryOrderBy ($dbQuery, array $query, string $defaultColumn='id', string $defaultOrder='desc')
     * @method static mixed queryHas ($dbQuery, array $query)
     * @method static mixed queryWhere ($dbQuery, array $query, array $columns)
     * @method static mixed queryWhereIn ($dbQuery, array $query, array $columns)
     * @method static mixed queryWhereNotIn ($dbQuery, array $query, array $columns)
     * @method static mixed queryGraphSQLEncrypted ($dbQuery, array $query, $model, callable $callback=null)
     * @method static mixed queryGraphSQLByKey ($dbQuery, array $query, $model, callable $callback=null)
     * @method static mixed queryGraphSQL ($dbQuery, array $query, $model, callable $callback=null)
     *
     * @see \Bitsmind\GraphSql\QueryAssist
     */
    protected static function getFacadeAccessor(): string
    {
        return 'graphsql-query-assist';
    }
}