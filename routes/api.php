<?php

use App\Http\Controllers\Api\AddressMigrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/v1/status', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Web service OK',
        'timestamp' => now()
    ]);
});

Route::post('/addresses/migrate', [AddressMigrationController::class, 'migrate'])
    ->name('api.addresses.migrate');
