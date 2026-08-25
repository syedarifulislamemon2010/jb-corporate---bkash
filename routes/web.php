<?php

use App\Filament\Pages\Auth\EnterTempPassword;
use App\Filament\Pages\Auth\ForgotPasswordMobile;
use App\Filament\Pages\Auth\SetNewPassword;
use App\Filament\Pages\Auth\VerifyOtp;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
})->name('admin');

Route::middleware(['web'])->group(function () {
    Route::get('/admin/forgot-password', ForgotPasswordMobile::class)->name('filament.admin.auth.forgot-password');
    Route::get('/admin/verify-otp', VerifyOtp::class)->name('filament.admin.auth.verify-otp');
    Route::get('/admin/enter-temp-password', EnterTempPassword::class)->name('filament.admin.auth.enter-temp-password');
    Route::get('/admin/set-new-password', SetNewPassword::class)->name('filament.admin.auth.set-new-password');
});

Route::fallback(function () {
    return redirect('/admin');
});
