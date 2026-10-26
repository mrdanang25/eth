<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceStatusController;

// Simple custom token middleware applied within the controller logic for minimal dependencies
Route::get('/devices', [DeviceStatusController::class, 'index']);
Route::post('/ping-results', [DeviceStatusController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
