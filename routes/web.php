<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountLedgerController;
use App\Http\Controllers\CurrencySettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\GoldHoldingController;
use App\Http\Controllers\IncomeSourceController;
use App\Http\Controllers\IncomeTransactionController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/accounts', [InstitutionController::class, 'index'])->name('institutions.index');
    Route::post('/institutions', [InstitutionController::class, 'store'])->name('institutions.store');
    Route::put('/institutions/{institution}', [InstitutionController::class, 'update'])->name('institutions.update');
    Route::delete('/institutions/{institution}', [InstitutionController::class, 'destroy'])->name('institutions.destroy');

    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('/accounts/{account}/ledger', [AccountLedgerController::class, 'show'])->name('accounts.ledger');

    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::put('/deposits/{deposit}', [DepositController::class, 'update'])->name('deposits.update');
    Route::delete('/deposits/{deposit}', [DepositController::class, 'destroy'])->name('deposits.destroy');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

    Route::get('/income', [IncomeSourceController::class, 'index'])->name('income-sources.index');
    Route::post('/income-sources', [IncomeSourceController::class, 'store'])->name('income-sources.store');
    Route::put('/income-sources/{incomeSource}', [IncomeSourceController::class, 'update'])->name('income-sources.update');
    Route::delete('/income-sources/{incomeSource}', [IncomeSourceController::class, 'destroy'])->name('income-sources.destroy');

    Route::post('/income-transactions/{incomeTransaction}/confirm', [IncomeTransactionController::class, 'confirm'])->name('income-transactions.confirm');
    Route::post('/income-transactions/{incomeTransaction}/unconfirm', [IncomeTransactionController::class, 'unconfirm'])->name('income-transactions.unconfirm');
    Route::post('/income-sources/{incomeSource}/confirm', [IncomeTransactionController::class, 'confirmForSource'])->name('income-sources.confirm');

    Route::get('/gold', [GoldHoldingController::class, 'index'])->name('gold-holdings.index');
    Route::post('/gold', [GoldHoldingController::class, 'store'])->name('gold-holdings.store');
    Route::put('/gold/{goldHolding}', [GoldHoldingController::class, 'update'])->name('gold-holdings.update');
    Route::delete('/gold/{goldHolding}', [GoldHoldingController::class, 'destroy'])->name('gold-holdings.destroy');

    Route::get('/gifts', [GiftController::class, 'index'])->name('gifts.index');
    Route::post('/gifts', [GiftController::class, 'store'])->name('gifts.store');
    Route::put('/gifts/{gift}', [GiftController::class, 'update'])->name('gifts.update');
    Route::delete('/gifts/{gift}', [GiftController::class, 'destroy'])->name('gifts.destroy');

    Route::get('/settings/currencies', [CurrencySettingController::class, 'edit'])->name('currency-settings.edit');
    Route::patch('/settings/currencies', [CurrencySettingController::class, 'update'])->name('currency-settings.update');
});

require __DIR__.'/auth.php';
