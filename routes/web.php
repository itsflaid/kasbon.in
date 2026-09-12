<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntryController;
use App\Livewire\Anggota;
use App\Livewire\Dashboard;
use App\Livewire\DebtorDetail;
use App\Livewire\PilihWarung;
use App\Livewire\Profil;
use App\Livewire\RiwayatGlobal;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
Route::get('/login', fn () => redirect()->route('google.redirect'))->name('login');

Route::get('/auth/google/redirect', [AuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'callback'])->middleware('throttle:10,1');

Route::get('/pilih-warung', PilihWarung::class)->name('pilih-warung');

Route::middleware(['auth', 'set-warung'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/debtors/{debtor}', DebtorDetail::class)->name('debtor.detail');
    Route::get('/riwayat', RiwayatGlobal::class)->name('riwayat');
    Route::get('/anggota', Anggota::class)->name('anggota');
    Route::get('/profil', Profil::class)->name('profil');

    Route::post('/warungs/{warung}/entries', [EntryController::class, 'store'])->name('entries.store')->middleware('throttle:30,1');
    Route::post('/warungs/{warung}/entries/confirm', [EntryController::class, 'confirmStore'])->name('entries.confirm-store')->middleware('throttle:30,1');
    Route::put('/entries/{entry}', [EntryController::class, 'update'])->name('entries.update')->middleware('throttle:30,1');
    Route::post('/entries/{entry}/void', [EntryController::class, 'void'])->name('entries.void')->middleware('throttle:30,1');
});
