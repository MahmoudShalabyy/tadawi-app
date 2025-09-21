<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/migrate', function () {
    \Artisan::call('migrate', ['--force' => true]);
    \Artisan::call('storage:link');
    return 'Migrations run successfully!';
});