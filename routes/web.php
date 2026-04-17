<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\InvestmentController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/claim-salary', [DashboardController::class, 'claimSalary'])->name('dashboard.claim-salary');
    
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
    Route::put('/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments');
    Route::get('/investments/assessment', [InvestmentController::class, 'assessment'])->name('investments.assessment');
    Route::post('/investments/profile', [InvestmentController::class, 'setProfile'])->name('investments.profile');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('role:admin')->group(function () {
        // User Management
        Route::get('/admin/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('admin.users');
        Route::get('/admin/users/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('admin.users.store');
        Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/admin/users/{id}/restore', [\App\Http\Controllers\Admin\AdminUserController::class, 'restore'])->name('admin.users.restore');
        Route::get('/admin/users/{user}/audits', [\App\Http\Controllers\Admin\AdminUserController::class, 'audits'])->name('admin.users.audits');
        Route::get('/admin/audits/calendar', [\App\Http\Controllers\Admin\AdminUserController::class, 'calendar'])->name('admin.users.calendar');

        Route::get('/admin/roles', function() { return view('admin.roles.index'); })->name('admin.roles');
        
        // Admin Investments
        Route::get('/admin/investments', [\App\Http\Controllers\Admin\AdminInvestmentController::class, 'index'])->name('admin.investments');
        Route::post('/admin/investments/assets', [\App\Http\Controllers\Admin\AdminInvestmentController::class, 'storeAsset'])->name('admin.assets.store');
        Route::post('/admin/investments/portfolio', [\App\Http\Controllers\Admin\AdminInvestmentController::class, 'updatePortfolio'])->name('admin.portfolio.update');
    });
});
