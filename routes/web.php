<?php

use Illuminate\Support\Facades\Route;
use Phpsa\StatamicXero\Http\Controllers\Cp\XeroController;

Route::middleware('web')->group(function () {

    Route::get('xero-authentication-success', function () {
         session()->flash('success', "Connected to Xero");
         return redirect('/cp/utilities/xero-authentication');
    })->name('xero.auth.success');

    Route::get(
        'xero-authenticate',
        [XeroController::class, 'authorise']
    )->name('xero.auth.setup');
});
