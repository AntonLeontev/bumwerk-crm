<?php

use App\Http\Controllers\LeadApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('leads', [LeadApiController::class, 'index']);
    Route::post('leads', [LeadApiController::class, 'store']);
});
