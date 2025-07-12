<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDashboard;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\ShowcaseController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\AboutController;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\XenditController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\ForgotPasswordController;




// routes for card image in resources
Route::get('/project-image/{filename}', function ($filename) {
    $path = resource_path('image/showcase/' . $filename);
    if (!File::exists($path)) {
        $path = resource_path('image/project/timeout.jpg');
    }
    return Response::file($path);
});

// page routes
Route::get('/', function () {
    return view('landing/welcome');
});

Route::controller(AuthController::class)->group(function () {
    Route::get('/register', 'register')->name('register');
    Route::post('/register', 'store')->name('register.store');
});

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'login')->name('login');
    Route::post('/login', 'attemptlogin')->name('login.attempt');
    Route::get('/logout', 'attemptlogout')->name('attemptlogout')->middleware('auth');
});

Route::controller(ForgotPasswordController::class)->group(function () {
    Route::get('/forgot-password',  'showForgetPasswordForm')->name('password.request');
    Route::post('/forgot-password','submitForgetPasswordForm')->name('password.email');
    Route::get('/reset-password', 'showResetPasswordForm')->name('password.reset');
    Route::post('/reset-password','submitResetPasswordForm')->name('password.update');
});

// Show form to request reset
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('password.request');

// Submit email to get reset link
Route::post('/forgot-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('password.email');

// Show reset form with token
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('password.reset');

// Submit new password
Route::post('/reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('password.update');


Route::get('/showcase', [ShowcaseController::class, 'index'])->name('showcase');

Route::get('/about', function () {
    return view('landing/about');
});

Route::get('/welcome', function () {
    return view('landing/welcome');
});

Route::get('/account', function () {
    return view('dashboard.dashboard');
});

Route::get('/user', [UserDashboard::class, 'index']);

Route::get('/admin', [AdminController::class, 'index'])->name('admin.work');
Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');

// project creation

Route::get('/project/add', [WorkController::class, 'create'])->name('project.create');
Route::post('/project/store', [WorkController::class, 'store'])->name('project.store');
Route::delete('/project/destroy/{id}', [WorkController::class, 'destroy'])->name('project.delete');

Route::controller(UserController::class)->group(function () {
    Route::get('/user/data', 'userData')->name('user.data')->middleware('auth');
});

Route::controller(XenditController::class)->group(function () {
    Route::get('/topup', 'viewTopup')->name('topup.view');
    Route::post('/topup/create', 'createPaymentRequest')->name('payment.create');
    Route::get('/topup/webhook', 'handleWebhook')->name('submit.payment');
    Route::get('/payout', 'payoutsView')->name('payouts.view');
    Route::post('/payout/create', 'submitPayouts')->name('payment.payouts.create');
});