<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntryController;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
Route::get('/login', fn () => redirect()->route('google.redirect'))->name('login');

Route::get('/auth/google/redirect', [AuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'callback'])->middleware('throttle:10,1');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::post('/warungs/{warung}/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::post('/warungs/{warung}/entries/confirm', [EntryController::class, 'confirmStore'])->name('entries.confirm-store');
    Route::put('/entries/{entry}', [EntryController::class, 'update'])->name('entries.update');
    Route::post('/entries/{entry}/void', [EntryController::class, 'void'])->name('entries.void');
});
