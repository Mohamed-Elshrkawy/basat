<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Rider\TripSearchController;
use App\Http\Controllers\Api\V1\Rider\TripController;
use App\Http\Controllers\Api\V1\Rider\BookingController;
use App\Http\Controllers\Api\V1\Rider\ChildController;
use App\Http\Controllers\Api\V1\Rider\SchoolServiceController;
use App\Http\Controllers\Api\V1\Rider\ProfileController;
use App\Http\Controllers\Api\V1\Driver\DriverController;
use App\Http\Controllers\Api\V1\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Api\V1\Driver\FcmController;
use App\Http\Controllers\Api\V1\Rider\WalletController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\NotificationController;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

// --- Public Routes (No Authentication Needed) ---
//Route::prefix('v1')->group(function () {
//    // Auth routes
//    Route::prefix('auth')->controller(AuthController::class)->group(function () {
//        Route::post('/register', 'register');
//        Route::post('/login', 'login');
//        Route::post('/verify-mobile', 'verifyMobile');
//        Route::post('/forgot-password', 'forgotPassword');
//        Route::post('/verify-reset-code', 'verifyResetCode');
//        Route::post('/reset-password', 'resetPassword');
//        Route::post('/resend-verification-code', 'resendVerificationCode');
//    });
//
//    // Public search and info routes for Riders (Guest Access)
//    Route::prefix('rider')->group(function () {
//        Route::get('/cities', [TripSearchController::class, 'getCities']);
//        Route::get('/search-trips', [TripSearchController::class, 'searchPublicBusTrips']);
//        Route::get('/schedules/{schedule}/details', [TripSearchController::class, 'getScheduleDetails']);
//        Route::get('/search-private-hire', [TripSearchController::class, 'searchPrivateHireDrivers']);
//        Route::get('/private-bus-seat-counts', [TripSearchController::class, 'getPrivateBusSeatCounts']);
//        Route::get('/school-packages', [SchoolServiceController::class, 'index']);
//        Route::get('/school-service/search', [\App\Http\Controllers\Api\V1\Rider\SchoolServiceController::class, 'search']);
//    });
//
//    // START: MODIFICATION - Refactored Content Routes
//    Route::prefix('content')->controller(ContentController::class)->group(function () {
//        Route::get('/faqs', 'getFaqs');
//        Route::get('/{key}', 'show')->where('key', 'about-us|privacy-policy|terms-and-conditions|cancellation-policy|contact-us');
//        Route::post('/contact-us', 'storeContactMessage');
//    });
//    // END: MODIFICATION
//});
//
//
//// --- Authenticated Routes ---
//Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
//    // Authenticated user routes
//    Route::prefix('auth')->controller(AuthController::class)->group(function () {
//        Route::post('/logout', 'logout');
//        Route::post('/delete-account', 'deleteAccount');
//        Route::post('/device-token', 'updateDeviceToken');
//    });
//
//    // START: MODIFICATION - Add Notification Routes
//    Route::controller(NotificationController::class)->group(function () {
//        Route::get('/notifications', 'index');
//        Route::post('/notifications/mark-as-read', 'markAsRead');
//        Route::post('/notifications/mark-all-as-read', 'markAllAsRead');
//    });
//    // END: MODIFICATION
//
//    // --- Rider Specific Routes ---
//    Route::prefix('rider')->middleware('role:rider')->group(function () {
//        Route::get('/profile', [ProfileController::class, 'show']);
//        Route::put('/profile', [ProfileController::class, 'update']);
//        Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
//        Route::post('/bookings', [BookingController::class, 'storePublicBusBooking']);
//        Route::post('/bookings/private-hire', [BookingController::class, 'storePrivateHireBooking']);
//
//        // Children management
//        Route::apiResource('children', ChildController::class);
//
//        // School Service
//        Route::post('/school-subscriptions', [SchoolServiceController::class, 'subscribe']);
//        Route::get('/my-school-subscriptions', [SchoolServiceController::class, 'mySchoolSubscriptions']);
//
//        // Trip management
//        Route::get('/my-trips', [TripSearchController::class, 'myTrips']);
//        Route::post('/trips/{trip}/cancel', [TripController::class, 'cancel']);
//        Route::post('/trips/{trip}/rate', [TripController::class, 'rate']);
//        Route::post('/trips/{trip}/report-problem', [TripController::class, 'reportProblem']);
//
//        // START: MODIFICATION - Add Wallet routes
//        Route::get('/wallet', [WalletController::class, 'show']);
//        Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
//        // END: MODIFICATION
//
//        // START: MODIFICATION - Add report absence route
//        Route::post('/school-subscriptions/{subscription}/report-absence', [SchoolServiceController::class, 'reportChildAbsence']);
//        // END: MODIFICATION
//
//        // START: MODIFICATION - Add driver tracking route
//        Route::get('/trips/{trip}/track', [TripController::class, 'trackDriver']);
//        // END: MODIFICATION
//    });
//
//    // --- Driver Specific Routes ---
//    Route::prefix('driver')->middleware('role:driver')->group(function () {
//        // Profile Management (جديد)
//        Route::get('/profile', [DriverProfileController::class, 'show']);
//        Route::put('/profile', [DriverProfileController::class, 'update']);
//        Route::post('/profile/change-password', [DriverProfileController::class, 'changePassword']);
//        Route::post('/update-status', [DriverController::class, 'updateStatus']);
//        Route::post('/update-location', [DriverController::class, 'updateLocation']);
//        Route::get('/trips', [DriverController::class, 'getTrips']);
//        Route::post('/trips/{trip}/update-status', [DriverController::class, 'updateTripStatus']);
//        Route::get('/earnings', [DriverController::class, 'getEarnings']);
//
//        Route::get('/trips/{trip}', [DriverController::class, 'getTripDetails']);
//        Route::post('/trips/check-in/{trip}', [DriverController::class, 'checkInPassenger'])->name('driver.trip.checkin');
//        Route::post('/trips/check-in-by-booking/{booking}', [DriverController::class, 'checkInPassengerByBooking']);
//        Route::post('/trips/{trip}/verify-private-hire', [DriverController::class, 'verifyPrivateHireTrip']);
//        Route::post('/school-subscriptions/{subscription}/update-status', [DriverController::class, 'updateStudentStatus']);
//        Route::post('/trips/{trip}/report-problem', [DriverController::class, 'reportProblem']);
//
//        // START: MODIFICATION - Add detailed earnings history route
//        Route::get('/earnings/history', [DriverController::class, 'getEarningsHistory']);
//        // END: MODIFICATION
//
//        // START: MODIFICATION - Add new route for wallet payment collection
//        Route::post('/trips/{trip}/collect-payment', [DriverController::class, 'collectPaymentByWallet']);
//        // END: MODIFICATION
//
//        // FCM Token Management
//        Route::prefix('fcm')->controller(FcmController::class)->group(function () {
//            Route::post('/token', 'updateToken');
//            Route::delete('/token', 'removeToken');
//            Route::get('/tokens', 'getTokens');
//        });
//    });
//});

// Test FCM Notification (for development)
//Route::post('/test-notification', [DriverController::class, 'sendTestNotification']);
