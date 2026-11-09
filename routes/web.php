<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Access\LoginController;
use App\Http\Controllers\Access\RegisterController;
use App\Http\Controllers\Access\ForgoutController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\Finance\WalletController;
use App\Http\Controllers\Plan\PlanController;
use App\Http\Controllers\Sale\SaleController;
use App\Http\Controllers\Sale\SaleExternalController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\ConsultantController;
use App\Http\Controllers\Gateway\GatewayController;

Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('logon', [LoginController::class, 'logon'])->name('logon');

Route::get('/forgout/{code?}', [ForgoutController::class, 'index'])->name('forgout');
Route::post('/forgout-password', [ForgoutController::class, 'forgoutPassword'])->name('forgout-password');
Route::post('/recover-password/{code}', [ForgoutController::class, 'recoverPassword'])->name('recover-password');

Route::get('/planos/{parent?}', [SaleExternalController::class, 'showcase'])->name('showcase');
Route::get('/create-sale/{plan}/{parent?}', [SaleExternalController::class, 'index'])->name('create-sale');
Route::get('/thank-you', [SaleExternalController::class, 'thankYou'])->name('thank-you');
Route::post('created-sale', [SaleExternalController::class, 'store'])->name('created-sale');
Route::get('/pay/{uuid}', [SaleExternalController::class, 'payShow'])->name('pay');
Route::post('/pay/{uuid}/accept', [SaleExternalController::class, 'acceptContract'])->name('pay-accept');
Route::get('/pay/{uuid}/status', [SaleExternalController::class, 'payStatus'])->name('pay-status');
Route::get('/pay/{uuid}/confirmed', [SaleExternalController::class, 'payConfirmed'])->name('pay-confirmed');
Route::post('/pay/{uuid}/card', [SaleExternalController::class, 'payCard'])->name('pay-card');

Route::middleware(['auth'])->group(function () {

    Route::get('/app', [AppController::class, 'index'])->name('app');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales-create');
    Route::post('/sales/create', [SaleController::class, 'storeInvite'])->name('sales-store');
    Route::get('/sale/{uuid}', [SaleController::class, 'show'])->name('sale');
    Route::post('/deleted-sale/{uuid}', [SaleController::class, 'destroy'])->name('deleted-sale');
    Route::post('/deleted-sales-all', [SaleController::class, 'destroyAll'])->name('deleted-sales-all');

    Route::get('/consultants', [ConsultantController::class, 'index'])->name('consultants');
    Route::get('/consultant/{uuid}', [ConsultantController::class, 'show'])->name('consultant');
    Route::post('/transfer-sale', [ConsultantController::class, 'transfer'])->name('transfer-sale');

    Route::get('wallet/{uuid}', [WalletController::class, 'show'])->name('wallet');

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

    Route::get('/gateway', [GatewayController::class, 'index'])->name('gateway');
    Route::post('/updated-gateway', [GatewayController::class, 'update'])->name('updated-gateway');

    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
});
