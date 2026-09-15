<?php

use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
});

// Public Informational & Legal Pages
Route::get('/about', function () {
    return view('pages.about');
});

Route::get('/contact', function () {
    return view('pages.contact');
});

Route::get('/privacy-policy', function () {
    return view('pages.privacy');
});

Route::get('/terms-and-conditions', function () {
    return view('pages.terms');
});

Route::get('/refund-policy', function () {
    return view('pages.refund');
});

Route::get('/account-deletion', function () {
    return view('pages.account-deletion');
});

// Catch-all route to serve the single page application (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
