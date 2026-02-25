<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\PertemuanController;
use App\Http\Controllers\Api\AbsensiController;

Route::prefix('v1')->group(function () {
    Route::get('mahasiswa', [MahasiswaController::class, 'index']);
    Route::post('mahasiswa', [MahasiswaController::class, 'store']);
    Route::get('mahasiswa/{nim}', [MahasiswaController::class, 'show']);
    Route::put('mahasiswa/{nim}', [MahasiswaController::class, 'update']);
    Route::delete('mahasiswa/{nim}', [MahasiswaController::class, 'destroy']);

    Route::get('pertemuan', [PertemuanController::class, 'index']);
    Route::post('pertemuan', [PertemuanController::class, 'store']);

    Route::get('absensi', [AbsensiController::class, 'index']);
    Route::post('absensi', [AbsensiController::class, 'store']);
    Route::delete('absensi/{id}', [AbsensiController::class, 'destroy']);
});
