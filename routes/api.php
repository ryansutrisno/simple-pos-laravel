<?php

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('api.transactions.show');
});
