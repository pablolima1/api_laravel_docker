<?php

use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/status', function() {
    return response()->json([
        "status" => "ok"
    ]);
});

Route::post('/transfer', [TransactionController::class, 'transfer']);