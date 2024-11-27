<?php

use App\Http\Controllers\Vouchers\DeleteVoucherHandler;
use App\Http\Controllers\Vouchers\GetVouchersAmountsHandler;
use App\Http\Controllers\Vouchers\GetVouchersHandler;
use App\Http\Controllers\Vouchers\RegularizeVoucherHandler;
use App\Http\Controllers\Vouchers\StoreVouchersHandler;
use Illuminate\Support\Facades\Route;

Route::prefix('vouchers')->group(
    function () {
        Route::get('/', GetVouchersHandler::class);
        Route::post('/', StoreVouchersHandler::class);
        Route::put('/regularize', RegularizeVoucherHandler::class);
        Route::get('/amounts', GetVouchersAmountsHandler::class);
        Route::delete('/{id}', DeleteVoucherHandler::class);
    }
);
