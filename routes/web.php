<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\Transactions;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\AffiliateManager;
use App\Livewire\Front\BookingTimeline;
use App\Livewire\Front\BookingForm;
use App\Livewire\Front\Payment;
use App\Livewire\Front\About;
use App\Livewire\Front\CheckOrder;
use App\Livewire\Auth\Login;
use App\Livewire\Affiliate\Login as AffiliateLogin;
use App\Livewire\Affiliate\Register as AffiliateRegister;
use App\Livewire\Affiliate\Dashboard as AffiliateDashboard;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes
Route::get('/login', Login::class)->name('login');

// Admin Routes protected by Authentication
Route::middleware('auth')->group(function () {
    Route::redirect('/admin', '/admin/dashboard');
    Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/units', UnitManager::class)->name('admin.units');
    Route::get('/admin/categories', CategoryManager::class)->name('admin.categories');
    Route::get('/admin/promo', PricingRules::class)->name('admin.promo');
    Route::get('/admin/transactions', Transactions::class)->name('admin.transactions');
    Route::get('/admin/settings', Settings::class)->name('admin.settings');
    Route::get('/admin/affiliate', AffiliateManager::class)->name('admin.affiliate');
    
    // Affiliate Dashboard
    Route::get('/affiliate/dashboard', AffiliateDashboard::class)->name('affiliate.dashboard');
    Route::get('/affiliate/payout', \App\Livewire\Affiliate\PayoutRequest::class)->name('affiliate.payout');
});

// Public Booking Routes
Route::get('/sewa', BookingTimeline::class)->name('public.timeline');
Route::get('/tentang', About::class)->name('public.about');
Route::get('/cek-pesanan', CheckOrder::class)->name('public.check-order');
Route::get('/booking', BookingForm::class)->name('public.booking');
Route::get('/payment/{booking_code}', Payment::class)->name('public.payment');
Route::get('/booking/success/{booking_code}', \App\Livewire\Front\Success::class)->name('public.success');

// Affiliate Public Routes
Route::get('/affiliate/login', AffiliateLogin::class)->name('affiliate.login');
Route::get('/affiliate/register', AffiliateRegister::class)->name('affiliate.register');
 
// Temporary route to clear cache on hosting - Delete after use
Route::get('/clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Semua cache berhasil dihapus!";
});
