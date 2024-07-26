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
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

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
