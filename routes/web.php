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

Route::get('/send-mail', function () {
    Mail::raw("Test email", function ($message) {
        $message->to("itiprojects7@gmail.com")->subject("Test");
    });

    return "Mail sent!";
});
