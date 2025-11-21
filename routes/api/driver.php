<?php

use App\Http\Controllers\Api\Driver\Auth\PasswordController;
use App\Http\Controllers\Api\Driver\Auth\AuthController;
use App\Http\Controllers\Api\Driver\BookingSeat\BookingController;
use App\Http\Controllers\Api\Driver\BookingPrivateBus\BookingPrivateBusController;
use App\Http\Controllers\Api\Driver\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

/** auth Routes **/
Route::controller(AuthController::class)->middleware('user.type')->group(function () {
    Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
        Route::post('/login', 'login')->name('login');
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

Route::middleware(['auth:api'])->group(callback: function () {

    /** Profile Settings Routes **/
    Route::controller(ProfileController::class)->middleware(['auth:api', 'user.type'])->prefix('profile')->group(function () {
        Route::get('/', 'show')->name('profile.show');
        Route::put('update-password', 'updatePassword')->name('profile.update.password');
        Route::put('/', 'update')->name('profile.update');
        Route::post('/language/switch/{locale}', 'updateLocale')->whereIn('locale', config('app.available_locales'))->name('profile.update.locale');
        Route::put('notification/switch', 'switchNotification')->name('profile.notification.switch');
    });

    Route::controller(BookingController::class)->prefix('trips')->group(function () {

        Route::get('/',  'index');

        // تفاصيل رحلة
        Route::get('{id}',  'show');

        // بدء رحلة
        Route::post('{id}/start',  'start');

        // إتمام رحلة
        Route::post('{id}/complete',  'complete');

        // ====================================
        // Station Progress - تقدم المحطات
        // ====================================

        // تسجيل الوصول لمحطة
        Route::post('{tripId}/stations/{stationProgressId}/mark-arrived',
             'markStationArrived');

        // تسجيل المغادرة من محطة
        Route::post('{tripId}/stations/{stationProgressId}/mark-departed',
             'markStationDeparted');

        // ====================================
        // Passenger Management - إدارة الركاب
        // ====================================

        // تسجيل حضور راكب (check-in)
        Route::post('{tripId}/passengers/{bookingId}/check-in',
             'checkInPassenger');

        // تسجيل صعود راكب (boarded)
        Route::post('{tripId}/passengers/{bookingId}/board',
             'boardPassenger');

        // تسجيل عدم حضور
        Route::post('{tripId}/passengers/{bookingId}/no-show',
             'markPassengerNoShow');
    });

    /** Private Bus Bookings Routes **/
    Route::controller(BookingPrivateBusController::class)->prefix('private-bookings')->group(function () {

        // عرض جميع الحجوزات الخاصة للسائق
        Route::get('/', 'index');

        // عرض تفاصيل حجز معين
        Route::get('{booking}', 'show');

        // قبول حجز
        Route::post('{booking}/accept', 'accept');

        // رفض حجز
        Route::post('{booking}/reject', 'reject');

        // تحديث حالة الرحلة
        Route::post('{booking}/update-trip-status', 'updateTripStatus');

        // بدء الرحلة
        Route::post('{booking}/start', 'startTrip');

        // إنهاء الرحلة
        Route::post('{booking}/complete', 'completeTrip');

        // إلغاء الحجز
        Route::post('{booking}/cancel', 'cancel');
    });

});
