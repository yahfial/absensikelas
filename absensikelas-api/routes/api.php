<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MahasiswaController;

Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});

Route::prefix('v1')->group(function () {
    Route::get('mahasiswa', [MahasiswaController::class, 'index']);
    Route::get('mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::post('mahasiswa', [MahasiswaController::class, 'store']);
    Route::put('mahasiswa/{nim}', [MahasiswaController::class, 'update']);
    Route::delete('mahasiswa/{nim}', [MahasiswaController::class, 'destroy']);
});