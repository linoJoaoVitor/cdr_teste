<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/relatorios');
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/esqueci-senha', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/esqueci-senha', [AuthController::class, 'forgot'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/redefinir-senha/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/redefinir-senha', [AuthController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');
});
Route::post('/sair', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/novo', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/importacoes', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/importacoes', [ImportController::class, 'store'])->name('imports.store');
        Route::get('/importacoes/{import}', [ImportController::class, 'show'])->name('imports.show');
    });
});
