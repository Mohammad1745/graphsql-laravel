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
     * @return array
     * @throws \ReflectionException
     */
    public function getSchema ($modelPath='app/Models', string $search=''): array
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
                $fillable = $model->getFillable();
                $hidden = $model->getHidden();
                if (count($hidden)) {
                    $fillable = array_values( array_filter($fillable, fn($value) => !in_array($value, $hidden)));
                }

                // search
                if (str_starts_with($tableName, $search)) {
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
                return "[$node]$pivot,$related";
            }
            else {
                return "[$node]$related";
            }

        }, $relations);

        return $relations;
    }
}
