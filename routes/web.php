<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Stock\Livewire\Adjust;
use App\Features\Stock\Livewire\Create;
use App\Features\Stock\Livewire\Edit;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Services\StockExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->role === 'admin'
        ? redirect()->route('admin.users')
        : redirect()->route('stocks.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::get('/dashboard', function () {
    return auth()->user()->role === 'admin'
        ? redirect()->route('admin.users')
        : redirect()->route('stocks.index');
})->middleware('auth')->name('dashboard');

Route::get('/profile', Profile::class)->middleware('auth')->name('profile');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:member'])->group(function () {
    Route::get('/stocks', StockList::class)->name('stocks.index');
    Route::get('/stocks/create', Create::class)->name('stocks.create');
    Route::get('/stocks/export', function (Request $request, StockExcelService $excelService) {
        return $excelService->exportFor($request->user(), [
            'search' => $request->query('search', ''),
            'status' => $request->query('status', ''),
        ]);
    })->name('stocks.export');
    Route::post('/stocks/import', function (Request $request, StockExcelService $excelService) {
        $validated = $request->validate([
            'excelFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);
        $count = $excelService->importFor($request->user(), $validated['excelFile']);

        return redirect()->route('stocks.index')->with('message', $count.' barang berhasil diimpor dari Excel.');
    })->name('stocks.import');
    Route::get('/stocks/{stock}', StockDetail::class)
        ->middleware('owns.stock')
        ->name('stocks.show');
    Route::get('/stocks/{stock}/edit', Edit::class)
        ->middleware('owns.stock')
        ->name('stocks.edit');
    Route::get('/stocks/{stock}/adjust', Adjust::class)
        ->middleware('owns.stock')
        ->name('stocks.adjust');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', UserList::class)->name('users');
    Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('users.stocks');
});
