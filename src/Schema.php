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
     * @param $modelPath
     * @return array
     * @throws \ReflectionException
     */
    public function getSchema ($modelPath='app/Models'): array
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

                $fillable = $model->getFillable();
                $hidden = $model->getHidden();
                if (count($hidden)) {
                    $fillable = array_values( array_filter($fillable, fn($value) => !in_array($value, $hidden)));
                }

                $models[] = [
                    'table' => $model->getTable(),
                    'fields' => $fillable,
                    'specialFields' => ['*', '_timestamps'],
                    'nodes' => $this->getModelRelations($model)
                ];
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

        return $index === false ? [] : array_slice($methods, 0, $index);
    }
}