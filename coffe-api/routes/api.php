<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('shops', [\App\Http\Controllers\Api\ShopController::class, 'index']);
Route::get('shops/{shop}', [\App\Http\Controllers\Api\ShopController::class, 'show']);
