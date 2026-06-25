<?php

use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
});

// Catch-all route to serve the single page application (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
