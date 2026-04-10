<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\Transactions;
use App\Livewire\Front\BookingTimeline;
use App\Livewire\Front\BookingForm;
use App\Livewire\Front\Payment;

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::get('/admin/units', UnitManager::class)->name('admin.units');
Route::get('/admin/promo', PricingRules::class)->name('admin.promo');
Route::get('/admin/transactions', Transactions::class)->name('admin.transactions');

// Public Booking Routes
Route::get('/timeline', BookingTimeline::class)->name('public.timeline');
Route::get('/booking', BookingForm::class)->name('public.booking');
Route::get('/payment/{id}', Payment::class)->name('public.payment');
