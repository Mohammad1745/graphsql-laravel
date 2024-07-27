<?php

namespace Bitsmind\GraphSql;

use Bitsmind\GraphSql\Models\GraphSqlKey;

trait QueryAssist
{
    /**
     * @param $dbQuery
     * @param array $query
     * @param int $defaultPage
     * @param int $defaultLength
     * @return mixed
     */
    protected function queryPagination ($dbQuery, array &$query, int $defaultPage = 1, int $defaultLength = 100): mixed
    {
        if (!array_key_exists('page', $query))      $query['page']      = $defaultPage;
        if (!array_key_exists('length', $query))    $query['length']    = $defaultLength;

        $offset = ($query['page']-1)*$query['length'];
        return $dbQuery->offset($offset)->limit($query['length']);
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @param string $defaultColumn
     * @param string $defaultOrder
     * @return mixed
     */
    protected function queryOrderBy ($dbQuery, array $query, string $defaultColumn='id', string $defaultOrder='desc'): mixed
    {
        if (array_key_exists('order_by', $query)) {
            [$column, $order] = explode(',',$query['order_by']);
            $dbQuery = $dbQuery->orderby($column, $order);
        }
        else {
            $dbQuery = $dbQuery->orderby($defaultColumn, $defaultOrder);
        }

        return $dbQuery;
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @return mixed
     */
    protected function queryHas ($dbQuery, array $query): mixed
    {
        if(array_key_exists('has', $query)) {
            $relations = explode(',', $query['has']);
            foreach ($relations as $relation ) {
                $dbQuery = $dbQuery->has($relation);
            }
        }

        return $dbQuery;
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @param array $columns
     * @return mixed
     */
    protected function queryWhere ($dbQuery, array $query, array $columns): mixed
    {
        foreach ($columns as $field) {
            if(array_key_exists($field, $query)) {
                $dbQuery = $dbQuery->where($field, $query[$field]);
            }
        }

        return $dbQuery;
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @param array $columns
     * @return mixed
     */
    protected function queryWhereIn ($dbQuery, array $query, array $columns): mixed
    {
        foreach ($columns as $field) {
            if(array_key_exists($field, $query)) {
                $dbQuery = $dbQuery->whereIn($field, explode(',', $query[$field]));
            }
        }

        return $dbQuery;
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @param array $columns
     * @return mixed
     */
    protected function queryWhereNotIn ($dbQuery, array $query, array $columns): mixed
    {
        foreach ($columns as $field) {
            if(array_key_exists($field, $query)) {
                $dbQuery = $dbQuery->whereNotIn($field, explode(',', $query[$field]));
            }
        }

        return $dbQuery;
    }

    /**
    $graphString = "{parent_id,name,parent{name,parent_id,image,parent{name,slug},children:2:10{name}}";
    to ->
    $graph = [
        "fields" => ['parent_id','name'],
        "nodes" => [
            [
                "title" => "parent",
                "foreign_keys" => [],
                "page" => 1,
                "limit" => 100,
                "fields" => ['name', 'parent_id', 'image'],
                "nodes" => [
                    [
                        "title" => "parent",
                        "foreign_keys" => [],
                        "page" => 1,
                        "limit" => 100,
                        "fields" => ['name', 'slug'],
                    ],
                    [
                        "title" => "children",
                        "foreign_keys" => [],
                        "page" => 2,
                        "limit" => 10,
                        "fields" => ['name']
                    ]
                ]
            ]
        ]
    ];
    to ->
    $dbQuery = $dbQuery->select(...$this->getSelectFields($graph['fields'], $model));
    $dbQuery = $dbQuery->with(['parent' => function($dbQuery) {
        $fields = ['name', 'parent_id', 'image'];
        $model = $dbQuery->getRelated();
        $dbQuery->select(...$this->getSelectFields($fields, $model));
        $dbQuery->with([
            'parent' => function($dbQuery) {
                $model = $dbQuery->getRelated();
                $fields = ['name','slug];
                $dbQuery->select(...$this->getSelectFields($fields, $model));
                $dbQuery->with([]);
            },
            'children' => function($dbQuery) {
                $fields = ['name'];
                $model = $dbQuery->getRelated();
                $dbQuery->select(...$this->getSelectFields($fields, $model));
                $dbQuery->with([]);
            }
        ]);
    }]);
     */

    /**
     * @param $dbQuery
     * @param array $query
     * @param $model
     * @param callable|null $callback
     * @return mixed
     * @throws \Exception
     */
    protected function queryGraphSQLByKey ($dbQuery, array $query, $model, callable $callback=null): mixed
    {
        $query['graph'] = "{*}";

        if (array_key_exists('graph_key', $query)) {

            if (file_exists( base_path('graph_sql_key.json'))) {

                $cachedData = file_get_contents(base_path('graph_sql_key.json'));

                if (!$cachedData) {
                    $allKeys = GraphSqlKey::select('key','string')->get();
                    file_put_contents(base_path('graph_sql_key.json'), $allKeys->toJson());
                    $cachedData = file_get_contents(base_path('graph_sql_key.json'));
                }

                $keys = json_decode($cachedData, true);
                $filtered = array_filter($keys, fn($el) => $el['key'] == $query['graph_key']);
                if (count($filtered)) $query['graph'] = array_values($filtered)[0]['string'];
                else throw new \Exception("Invalid graph_key '" . $query['graph_key'] . "'");

            }
            else {
                $data = GraphSqlKey::where('key', $query['graph_key'])->first();
                if ($data) $query['graph'] = $data->string;
                else throw new \Exception("Invalid graph_key '" . $query['graph_key'] . "'");
            }
        }

        return $this->queryGraphSQL($dbQuery, $query, $model, $callback);
    }

    /**
     * @param $dbQuery
     * @param array $query
     * @param $model
     * @param callable|null $callback
     * @return mixed
     * @throws \Exception
     */
    protected function queryGraphSQL ($dbQuery, array $query, $model, callable $callback=null): mixed
    {
        $graphString = array_key_exists('graph', $query) ? $query['graph'] : "{*}";

        $graph = $this->parseGraphString($graphString);

        $fields = $this->getSelectFields($graph['fields'], $model, $callback);

        if (array_key_exists('nodes', $graph)) {
            $this->insertForeign($graph, $fields, $model);
        }
        $dbQuery = $dbQuery->select(...$fields);

        if (array_key_exists('nodes', $graph)) {
            $dbQuery = $dbQuery->with( $this->queryGraphNodes($graph['nodes']));
        }

        return $dbQuery;
    }

    /**
     * @param array $nodes
     * @return array
     */
    protected function queryGraphNodes (array $nodes): array
    {
        $relations = [];
        foreach ($nodes as $node) {
            $relations[$node["title"]] = function($dbQuery)  use ($node) {
                $model = $dbQuery->getRelated();
                $queryable = $model->queryable ?? [];

                // push any foreign key required for child model
                $fields = [
                    ...$this->getSelectFields($node['fields'], $model),
                    ...$node['foreign_keys']
                ];

                if (array_key_exists('nodes', $node)) {
                    $this->insertForeign($node, $fields, $model);
                }

                $dbQuery->select( ...$fields);

                foreach ($node['where'] as $statement) {
                    if (in_array($statement[0], $queryable)) {
                        $dbQuery->where(...$statement);
                    }
                    else {
                        throw new \Exception("Invalid key '" . $statement[0] . "' for query on node '" . $node['title'] . "'");
                    }
                }

                if ($node['relation_type'] == 'HasMany' && $node['page'] > 0 && $node['length'] > 0) {
                    $offset = ($node['page'] - 1) * $node['length'];
                    $dbQuery->offset($offset)->limit($node['length']);
                }

                if (array_key_exists('nodes', $node)) {
                    $dbQuery->with( $this->queryGraphNodes($node['nodes']));
                }
            };
        }

        return $relations;
    }

    /**
     * @param array $fields
     * @param $model
     * @param callable|null $callback
     * @return array
     */
    protected function getSelectFields (array $fields, $model, callable $callback=null): array
    {
        $tableName = $model->getTable();
        $availableFields = $this->getAvailableDataSet($model);

        $dataSet = $fields == ['*'] ? $availableFields : array_intersect( $availableFields, $fields); // array_intersect([1,2,3],[2,3,4]) -> [2,3]

        $selects = ["$tableName.id"];
        foreach ($dataSet as $field) {
            $selects[] = "$tableName.$field";
        }
        if ($callback) {
            $selects = $callback($selects);
        }
        if (in_array('_timestamps', $fields) || in_array('*', $fields)) {
            $selects[] = "$tableName.created_at";
            $selects[] = "$tableName.updated_at";
        }

        return $selects;
    }


    /**
     * @param $model
     * @param bool $withSpecialFields
     * @return array
     */
    protected function getAvailableDataSet ($model, bool $withSpecialFields = false): array
    {
        $tableName = $model->getTable();
        $fillable = $model->getFillable();
        $hidden = $model->getHidden();
        $availableDataSet = array_values( array_filter($fillable, fn($value) => !in_array($value, $hidden)));

        if ($withSpecialFields) {
            $availableDataSet[] = '*';
            $availableDataSet[] = '_timestamps';
        }

        return $availableDataSet;
    }

    /**
     * @param array $node
     * @param array $fields
     * @param $model
     * @return void
     */
    protected function insertForeign (array &$node, array &$fields, $model): void
    {
        foreach ($node['nodes'] as $key => $child_node) {

            $relation = $model->{$child_node["title"]}();

            if (strpos(get_class($relation), 'HasMany')) {
                $node['nodes'][$key]['relation_type'] = "HasMany";
            }

            if (strpos(get_class($relation), 'BelongsTo')) {
                // product belongs to category, so products.category_id is needed
                $fields[] = $relation->getForeignKeyName();
            }
            else {
                // category has many products, so products.category_id is needed
                $node['nodes'][$key]['foreign_keys'][] = $relation->getForeignKeyName();
            }
            //TODO::belongsToMany
        }
    }

    /**
     * @param string $graphString
     * @param string|null $title
     * @return array
     * @throws \Exception
     */
    protected function parseGraphString (string $graphString, string $title=null): array
    {
        $graph = [];
        if ($title) {
            $graph['relation_type'] = null;
            $graph['foreign_keys'] = [];

            [$title, $where, $page, $length] = $this->parseTitleString($title);

            $graph['title'] = $title;
            $graph['where'] = $where;
            $graph['page'] = $page;
            $graph['length'] = $length;
        }

        $comma_index = 0;
        $parenthesis_count = 0;
        $prev_parenthesis_count=0;
        $curly_brace_count = 0;
        $prev_curly_brace_count=0;
        for ($i=0; $i<strlen($graphString); $i++) {

            $ch = $graphString[$i];

            if ($ch == '(') {
                $parenthesis_count++;
            }
            else if ($ch == ')') {
                $parenthesis_count--;
            }

            if ($ch == '{') {
                $curly_brace_count++;
            }
            else if ($ch == '}') {
                $curly_brace_count--;
            }

            if ($parenthesis_count>0 || $curly_brace_count>1 || ($curly_brace_count == 1 && $prev_curly_brace_count==2)) {
                /**
                 * skipped
                 * {name,category_id,{items(status=1,type=2){name,desc},image}}
                 *                   ^     ^    ^    ^^     ^
                 * 1    1           1_2    3    3    2_2    1_0  -> $curly_brace_count
                 * 0    1           1_1    2    3    3_2    2_1  -> $prev_curly_brace_count
                 * to pick the entire sub-graph string: {items(status=1,type=2){name,desc},image}
                 * then next step
                 * {items{name,desc},image}
                 *       ^    ^    ^
                 * 1     2    2    1      0  -> $curly_brace_count
                 * 0     1    2    2      1  -> $prev_curly_brace_count
                 * to pick the entire sub-graph string: items{name,desc}
                **/
            }
            else if ($ch == ',') {
                $substr = substr($graphString, $comma_index+1, $i-$comma_index-1);
                $bracePos = strpos($substr, '{');
                $bracePos ?
                    $graph['nodes'][] = $this->parseGraphString(substr($substr, $bracePos), substr($substr,0, $bracePos))
                    : $graph['fields'][] = $substr;

                $comma_index = $i;
            }
            else if ($ch == '}') {
                if ($curly_brace_count==0) {
                    $substr = substr($graphString, $comma_index+1, $i-$comma_index-1);
                    $bracePos = strpos($substr, '{');
                    $bracePos ?
                        $graph['nodes'][] = $this->parseGraphString(substr($substr, $bracePos), substr($substr,0, $bracePos))
                        : $graph['fields'][] = $substr;
                }
                else {
                    $substr = substr($graphString, $comma_index+1, $i-$comma_index);
                    $graph['fields'][] = $substr;
                }

                $comma_index = $i;
            }
            $prev_curly_brace_count = $curly_brace_count;
            $prev_parenthesis_count = $parenthesis_count;
        }
        return $graph;
    }

    /**
     * @param string $titleString
     * @return array
     * @throws \Exception
     */
    protected function parseTitleString (string $titleString): array
    {
        $title = "";
        $where = [];
        $page = 0;
        $length = 0;

        $comma_index = 0;
        $parenthesis_count = 0;
        $prev_parenthesis_count=0;
        for ($i=0; $i<strlen($titleString); $i++) {

            $ch = $titleString[$i];

            if ($ch == '(') {
                $parenthesis_count++;
            }
            else if ($ch == ')') {
                $parenthesis_count--;
            }

            /**
             * attributes(status=0,title=red):2:5
             *           ^
             * */
            if ($parenthesis_count==0 && $prev_parenthesis_count==0) {
                $title .= $ch;
                $comma_index = $i;
            }
            if ($parenthesis_count==1 && $prev_parenthesis_count==0) {
                $comma_index = $i;
            }
            else if ($ch == ',' || $ch == ')') {
                $where []= $this->parseWhereClause(substr($titleString, $comma_index+1, $i-$comma_index-1));

                $comma_index = $i;
            }
            $prev_parenthesis_count = $parenthesis_count;
        }

        $arr = explode(':', $title);//attributes:2:5
        if (count($arr)==3) {
            $title = $arr[0];  // attributes
            $page = $arr[1];   // 2
            $length = $arr[2]; // 5
        }
        else if (count($arr)==2) {
            $title = $arr[0];
            $length = $arr[1];
        }

        return [$title, $where, $page, $length];
    }

    /**
     * @param string $whereString
     * @return array
     * @throws \Exception
     */
    protected function parseWhereClause(string $whereString): array
    {
        if(strpos($whereString, '!=')) {
            $arr = explode('!=', $whereString);
            return [$arr[0], '!=', $arr[1]];
        }
        else if(strpos($whereString, '=')) {
            $arr = explode('=', $whereString);
            return [$arr[0], $arr[1]];
        }
        else {
            throw new \Exception("Unknown query '$whereString'");
        }
    }
}
