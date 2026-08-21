<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Access\LoginController;
use App\Http\Controllers\Access\RegisterController;
use App\Http\Controllers\Access\ForgoutController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Plan\PlanController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\Sale\SaleExternalController;
use App\Http\Controllers\User\UserController;

Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('logon', [LoginController::class, 'logon'])->name('logon');

Route::get('register/{indicator?}', [RegisterController::class, 'index'])->name('register');
Route::post('created-user', [RegisterController::class, 'store'])->name('created-user');

Route::get('/forgout/{code?}', [ForgoutController::class, 'index'])->name('forgout');
Route::post('/forgout-password', [ForgoutController::class, 'forgoutPassword'])->name('forgout-password');
Route::post('/recover-password/{code}', [ForgoutController::class, 'recoverPassword'])->name('recover-password');

Route::get('/create-sale/{plan}/{parent?}', [SaleExternalController::class, 'index'])->name('create-sale');
Route::get('/thank-you', [SaleExternalController::class, 'thankYou'])->name('thank-you');
Route::post('created-sale', [SaleExternalController::class, 'store'])->name('created-sale');

Route::middleware(['auth'])->group(function () {

    Route::get('/app', [AppController::class, 'index'])->name('app');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales');
    Route::get('/sale/{uuid}', [SaleController::class, 'show'])->name('sale');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans');
    Route::get('/plan/{uuid}', [PlanController::class, 'show'])->name('plan');
    Route::post('/created-plan', [PlanController::class, 'store'])->name('created-plan');
    Route::post('/updated-plan/{uuid}', [PlanController::class, 'update'])->name('updated-plan');
    Route::post('/deleted-plan/{uuid}', [PlanController::class, 'destroy'])->name('deleted-plan');

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/user/{uuid}', [UserController::class, 'show'])->name('user');
    Route::post('/created-user', [UserController::class, 'store'])->name('created-user');
    Route::post('/updated-user/{uuid}', [UserController::class, 'update'])->name('updated-user');
    Route::post('/deleted-user/{uuid}', [UserController::class, 'destroy'])->name('deleted-user');

    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
});
