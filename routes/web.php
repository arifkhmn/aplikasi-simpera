<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\WarrantyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Simpera
|--------------------------------------------------------------------------
| Seluruh rute privat dilindungi oleh middleware 'auth'.
| Pengguna yang belum login otomatis dialihkan ke /login.
*/

Route::middleware('auth')->group(function () {
    // Dashboard Utama Simpera
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Dokumen (Document Vault)
    Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/dokumen/tambah', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/dokumen', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/dokumen/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/dokumen/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/dokumen/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/dokumen/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/dokumen/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/dokumen/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::post('/dokumen/{document}/favorite', [DocumentController::class, 'toggleFavorite'])->name('documents.favorite');

    // Modul Pengingat (Smart Reminders)
    Route::get('/pengingat', [ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/pengingat/tambah', [ReminderController::class, 'create'])->name('reminders.create');
    Route::post('/pengingat', [ReminderController::class, 'store'])->name('reminders.store');
    Route::get('/pengingat/{reminder}', [ReminderController::class, 'show'])->name('reminders.show');
    Route::get('/pengingat/{reminder}/edit', [ReminderController::class, 'edit'])->name('reminders.edit');
    Route::put('/pengingat/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('/pengingat/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::post('/pengingat/{reminder}/toggle-status', [ReminderController::class, 'toggleStatus'])->name('reminders.toggle');

    // Modul Garansi (Warranty Tracker)
    Route::get('/garansi', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/garansi/tambah', [WarrantyController::class, 'create'])->name('warranties.create');
    Route::post('/garansi', [WarrantyController::class, 'store'])->name('warranties.store');
    Route::get('/garansi/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::get('/garansi/{warranty}/edit', [WarrantyController::class, 'edit'])->name('warranties.edit');
    Route::put('/garansi/{warranty}', [WarrantyController::class, 'update'])->name('warranties.update');
    Route::delete('/garansi/{warranty}', [WarrantyController::class, 'destroy'])->name('warranties.destroy');
});

// Rute Autentikasi (Login, Register, Logout)
require __DIR__.'/auth.php';
