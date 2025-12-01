<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;


// Dashboard routes
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/login', [DashboardController::class, 'login'])->name('login');
Route::post('/login', [DashboardController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

Route::get('/dashboard/history', [DashboardController::class, 'history'])->name('dashboard.history');
Route::get('/dashboard/balance', [DashboardController::class, 'getUserBalance'])->name('dashboard.balance');
