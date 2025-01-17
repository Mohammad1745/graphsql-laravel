<?php

use Illuminate\Support\Facades\Route;
use Bitsmind\GraphSql\Facades\Schema;

Route::get('graphsql/diagram', function () {
    $schema = Schema::getSchema();
    return view('graphsql::diagram.index', ['schema' => $schema]);
});
