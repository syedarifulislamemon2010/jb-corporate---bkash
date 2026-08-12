<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
})->name('admin');
Route::fallback(function () {
    return redirect('/admin');
});
