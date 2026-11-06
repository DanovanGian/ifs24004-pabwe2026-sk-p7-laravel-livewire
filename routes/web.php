<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Stock\Adjust;
use App\Livewire\Stock\Create;
use App\Livewire\Stock\Dashboard;
use App\Livewire\Stock\Edit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/stocks/create', Create::class)->name('stocks.create');
    Route::get('/stocks/{stock}/edit', Edit::class)->name('stocks.edit');
    Route::get('/stocks/{stock}/adjust', Adjust::class)->name('stocks.adjust');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
