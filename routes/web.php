<?php

use Illuminate\Support\Facades\Route;
use Phpsa\StatamicXero\Http\Controllers\Cp\XeroController;

Route::middleware('web')->group(function () {
    Route::get('/cp/utilities/xero-authentication', [XeroController::class, 'manage'])->name('xero.auth.success');
});
