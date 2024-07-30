<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CryptoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/price', [CryptoController::class, 'getPrice']);
Route::get('/alerts', [CryptoController::class, 'getAlerts']);