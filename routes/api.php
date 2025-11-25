// routes/api.php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DosenController;
use App\Http\Controllers\Api\MahasiswaController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Admin routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::get('/matakuliah', [AdminController::class, 'getMatakuliah']);
        Route::post('/matakuliah', [AdminController::class, 'createMatakuliah']);
        Route::get('/kelas', [AdminController::class, 'getKelas']);
        Route::post('/kelas', [AdminController::class, 'createKelas']);
        Route::post('/kelas/{kelasId}/mahasiswa', [AdminController::class, 'addMahasiswaToKelas']);
        Route::get('/dashboard', [AdminController::class, 'getDashboardStats']);
        Route::get('/laporan-absensi', [AdminController::class, 'getLaporanAbsensi']);
    });

    // Dosen routes
    Route::prefix('dosen')->middleware('role:dosen')->group(function () {
        Route::get('/dashboard', [DosenController::class, 'getDashboard']);
        Route::get('/kelas', [DosenController::class, 'getKelasDosen']);
        Route::get('/kelas/{kelasId}/absensi', [DosenController::class, 'getAbsensiKelas']);
        Route::post('/absensi/manual', [DosenController::class, 'inputAbsensiManual']);
        Route::get('/kelas/{kelasId}/rekap', [DosenController::class, 'getRekapAbsensi']);
    });

    // Mahasiswa routes
    Route::prefix('mahasiswa')->middleware('role:mahasiswa')->group(function () {
        Route::get('/dashboard', [MahasiswaController::class, 'getDashboard']);
        Route::get('/kelas', [MahasiswaController::class, 'getKelasMahasiswa']);
        Route::post('/absen/masuk', [MahasiswaController::class, 'absenMasuk']);
        Route::get('/absensi/riwayat', [MahasiswaController::class, 'getRiwayatAbsensi']);
        Route::get('/absensi/rekap', [MahasiswaController::class, 'getRekapAbsensiMahasiswa']);
        Route::post('/izin', [MahasiswaController::class, 'ajukanIzin']);
    });
});