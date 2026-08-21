<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::get('/dashboard', Dashboard::class)->name('dashboard');

Route::get('/auth/google/redirect', [AuthController::class, 'redirect']);
Route::get('/auth/google/callback', [AuthController::class, 'callback']);
