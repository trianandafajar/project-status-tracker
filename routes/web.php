<?php

use App\Http\Controllers\StatusPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StatusPageController::class, 'index']);

Route::get('/app', function () {
    return view('app');
});
