<?php

namespace Bitsmind\GraphSql\Commands;

use Illuminate\Console\Command;

class ClearGraphSqlCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'graphsql:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears graph_sql_key.json file ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (file_exists( base_path('graph_sql_key.json'))) {

            file_put_contents(base_path('graph_sql_key.json'), "");

            printf("Cleared Cache File \n");

        }
        else {

            printf("No Cache File \n");

        }
    }
}
