<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('filament.admin.locale.switch')
    ->middleware(['web', 'auth']);
