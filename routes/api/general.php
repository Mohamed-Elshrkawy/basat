<?php

use App\Enums\StaticPageType;
use App\Http\Controllers\Api\General\General\GeneralController;
use App\Http\Controllers\Api\General\Notification\NotificationController;
use App\Http\Controllers\Api\General\Pages\PagesController;
use App\Http\Controllers\Api\General\Wallet\WalletController;
use Illuminate\Support\Facades\Route;


/** General Routes **/
Route::controller(GeneralController::class)->group(function () {
    Route::get('cities', 'cities')->name('cities');
    Route::get('settings', 'settings')->name('settings');
});

/** Pages Routes **/
Route::controller(PagesController::class)->prefix('pages')->group(function () {
    Route::get('/pages', 'pages');
    Route::get('/page/{page}', 'showPage')->whereIn('page', StaticPageType::values());
    Route::post('/contact', 'contactSubmit');
    Route::get('/faqs', 'faq');
});


/** Notifications Routes **/
Route::controller(NotificationController::class)->middleware(['auth:api', 'user.type'])->prefix('notifications')->group(function () {
    Route::get('/', 'index')->name('notifications.index');
    Route::put('/{notification?}', 'markAsRead')->name('notifications.mark.as.read');
    Route::delete('/{notification?}', 'destroy')->name('notifications.destroy');
});


/** Wallet Routes **/
Route::controller(WalletController::class)->middleware(['auth:api', 'user.type'])->prefix('wallet')->group(function () {
    Route::get('/', 'index')->name('wallet.index');
    Route::get('/transactions', 'transactions')->name('wallet.transactions');
    Route::post('/withdraw', 'withdrawRequest')->name('wallet.withdraw');
});




