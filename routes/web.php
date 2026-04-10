<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\Transactions;
use App\Livewire\Front\BookingTimeline;
use App\Livewire\Front\BookingForm;
use App\Livewire\Front\Payment;
use App\Livewire\Auth\Login;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes
Route::get('/login', Login::class)->name('login');

// Admin Routes protected by Authentication
Route::middleware('auth')->group(function () {
    Route::redirect('/admin', '/admin/dashboard');
    Route::get('/admin/dashboard', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/units', UnitManager::class)->name('admin.units');
    Route::get('/admin/promo', PricingRules::class)->name('admin.promo');
    Route::get('/admin/transactions', Transactions::class)->name('admin.transactions');
});

// Public Booking Routes
Route::get('/timeline', BookingTimeline::class)->name('public.timeline');
Route::get('/booking', BookingForm::class)->name('public.booking');
Route::get('/payment/{id}', Payment::class)->name('public.payment');
