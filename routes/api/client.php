<?php

use App\Http\Controllers\Api\Client\Auth\AuthController;
use App\Http\Controllers\Api\Client\Auth\PasswordController;
use App\Http\Controllers\Api\Client\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

/** auth Routes **/
Route::controller(AuthController::class)->middleware('user.type')->group(function () {
    Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
        Route::post('/login', 'login')->name('login');
        Route::post('/register', 'register')->name('register');
        Route::post('/send', 'resendOtp')->name('resend.Otp');
        Route::post('/verify', 'verify')->name('verify');
    });

    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('logout', 'logout')->name('logout');
        Route::post('/token/refresh', 'refreshToken')->name('refresh.token');
    });
});

/** password Routes **/
Route::controller(PasswordController::class)->prefix('password')->group(function () {
    Route::post('/forget', 'forget')->name('forget');
    Route::post('/verify', 'verify')->name('verify');
    Route::post('/reset', 'reset')->name('reset');
});

Route::middleware(['auth:api', 'client'])->group(callback: function () {

    /** Profile Settings Routes **/
    Route::controller(ProfileController::class)->middleware(['auth:api', 'user.type'])->prefix('profile')->group(function () {
        Route::get('/', 'show')->name('profile.show');
        Route::put('update-password', 'updatePassword')->name('profile.update.password');
        Route::put('/', 'update')->name('profile.update');
        Route::post('/language/switch/{locale}', 'updateLocale')->whereIn('locale', config('translatable.locales'))->name('profile.update.locale');
        Route::put('notification/switch', 'switchNotification')->name('profile.notification.switch');
        Route::post('delete/account', 'deleteAccount')->name('profile.delete.account');
    });

});
