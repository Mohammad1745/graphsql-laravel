<?php

namespace Bitsmind\GraphSql\Providers;

use Illuminate\Support\ServiceProvider;

class GraphSqlServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('graphsql-query-assist', function () {
            return new \Bitsmind\GraphSql\Services\QueryAssistService();
        });
        $this->app->singleton('graphsql-schema', function () {
            return new \Bitsmind\GraphSql\Services\SchemaService();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'graphsql');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bitsmind\GraphSql\Commands\ClearGraphSqlCache::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
