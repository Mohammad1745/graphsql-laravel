<?php

namespace Bitsmind\GraphSql;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Schema
{
    /**
     * @param string $modelPath
     * @param string $search
     * @param bool $showHidden
     * @return array
     * @throws \ReflectionException
     */
    public function getSchema (string $modelPath='app/Models', string $search='', bool $showHidden=false): array
    {

        $files = File::allFiles( app_path( str_replace('app/', '', $modelPath)));

        $models = [];
        foreach ($files as $file) {
            $namespace = app()->getNamespace();
            $className = $namespace . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($file->getPathname(), app_path() . DIRECTORY_SEPARATOR)
                );
            if (is_subclass_of($className, 'Illuminate\Database\Eloquent\Model') && !(new \ReflectionClass($className))->isAbstract()) {
                $model = App::make($className);
                $tableName = $model->getTable();
                // search
                if (str_starts_with($tableName, $search)) {
                    $fillable = $model->getFillable();
                    $hidden = $model->getHidden();
                    if (!$showHidden && count($hidden)) {
                        $fillable = array_values( array_filter($fillable, fn($value) => !in_array($value, $hidden)));
                    }

//                    $fillable = array_map(function ($column) use ($model) {
//                        $table = $model->getTable();
//                        $type = IlluminateSchema::getColumnType($table, $column, true);
//
//                        return [
//                            'column' => $column,
//                            'type' => $type,
//                        ];
//                    }, $model->getFillable());

                    $models[] = [
                        'table' => $tableName,
                        'fields' => $fillable,
                        'specialFields' => ['*', '_timestamps'],
                        'nodes' => $this->getModelRelations($model)
                    ];
                }
            }
        }

        return $models;
    }

    /**
     * @param Model $model
     * @return array|string[]
     */
    private function getModelRelations(Model $model): array
    {
        $methods = get_class_methods($model);
        $index = array_search('__construct', $methods);
        $relations = $index === false ? [] : array_slice($methods, 0, $index);

        $relations = array_map( function ($node) use ($model) {
            $relation = $model->{$node}();
            $classPath = get_class($relation);
            $relationName = str_replace("Illuminate\Database\Eloquent\Relations\\", '', $classPath);

            $related = $relation->getRelated()->getTable();

            if (strpos($classPath, 'BelongsToMany')) {
                $pivot = $relation->getTable();
                return [
                    'title' => $node,
                    'pivot' => $pivot,
                    'table' => $related
                ];
            }
            else {
                return [
                    'title' => $node,
                    'table' => $related
                ];
            }

        }, $relations);

        return $relations;
    }
}
