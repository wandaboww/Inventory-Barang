<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'publicIndex'])->name('dashboard.public');

Route::get('/admin/login', [DashboardController::class, 'showAdminLogin'])->name('admin.login.form');
Route::post('/admin/login', [DashboardController::class, 'loginAdmin'])->name('admin.login');
Route::post('/admin/logout', [DashboardController::class, 'logoutAdmin'])->middleware('admin.access')->name('admin.logout');
Route::get('/admin/dashboard', [DashboardController::class, 'adminIndex'])->middleware('admin.access')->name('dashboard.admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('admin.access')->group(function (): void {
        Route::resource('assets', AssetController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings/running-text', [SettingController::class, 'updateRunningText'])->name('settings.running-text.update');
        Route::put('settings/menu-a', [SettingController::class, 'updateMenuA'])->name('settings.menu-a.update');
        Route::put('settings/menu-b', [SettingController::class, 'updateMenuB'])->name('settings.menu-b.update');
        Route::put('settings/menu-c', [SettingController::class, 'updateMenuC'])->name('settings.menu-c.update');
    });

    Route::post('loans/borrow', [LoanController::class, 'borrow'])->name('loans.borrow');
    Route::post('loans/return', [LoanController::class, 'returnItem'])->name('loans.return');
});
