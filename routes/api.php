<?php

use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Gateway\AssasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('invoices', [FinanceController::class, 'invoices'])->name('invoices');

Route::post('webhook', [AssasController::class, 'webhook'])->name('webhook');
