<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\Transactions;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\AffiliateManager;
use App\Livewire\Admin\CustomerManager;
use App\Livewire\Front\BookingTimeline;
use App\Livewire\Front\BookingForm;
use App\Livewire\Front\Payment;
use App\Livewire\Front\About;
use App\Livewire\Front\CheckOrder;
use App\Livewire\Front\CustomerLogin;
use App\Livewire\Front\CustomerLogout;
use App\Livewire\Auth\Login;
use App\Livewire\Affiliate\Login as AffiliateLogin;
use App\Livewire\Affiliate\Register as AffiliateRegister;
use App\Livewire\Affiliate\Dashboard as AffiliateDashboard;

Route::get('/', function () {
    return view('welcome');
})->name('public.home');

// Auth Routes
Route::get('/login', Login::class)->name('login');

// Admin Routes protected by Authentication
Route::middleware('auth')->group(function () {
    Route::redirect('/admin', '/admin/dashboard');
    
    // Strictly Admin Management
    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');
        Route::get('/admin/units', UnitManager::class)->name('admin.units');
        Route::get('/admin/campaign', \App\Livewire\Admin\AnnouncementManager::class)->name('admin.campaign');
        Route::get('/admin/promo', PricingRules::class)->name('admin.promo');
        Route::get('/admin/transactions', Transactions::class)->name('admin.transactions');
        Route::get('/admin/monitoring', \App\Livewire\Admin\Monitoring::class)->name('admin.monitoring');
        Route::get('/admin/customers', CustomerManager::class)->name('admin.customers');
        Route::get('/admin/settings', Settings::class)->name('admin.settings');
        Route::get('/admin/affiliate', AffiliateManager::class)->name('admin.affiliate');
        Route::get('/admin/stafflogs', \App\Livewire\Admin\StaffLogs::class)->name('admin.staff-logs');
        Route::get('/admin/notes', \App\Livewire\Admin\Notes::class)->name('admin.notes');
        Route::get('/admin/ratings', \App\Livewire\Admin\RatingManager::class)->name('admin.ratings');
        Route::get('/admin/scan', \App\Livewire\Admin\QuickScan::class)->name('admin.scan');
        Route::get('/admin/radar', \App\Livewire\Admin\RadarDevices::class)->name('admin.radar');
        Route::get('/admin/instagram-story', \App\Livewire\Admin\InstagramStory::class)->name('admin.instagram-story');
        Route::get('/admin/whatsapp-qr', function() {
            $botUrl = config('services.whatsapp.url', 'http://localhost:3001');
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(5)->get("{$botUrl}/qr");
                return response($res->body(), $res->status())->header('Content-Type', 'text/html');
            } catch (\Exception $e) {
                return "<div style='font-family:sans-serif;padding:2rem;text-align:center;color:#333;background:#f8fafc;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;'>
                    <h2 style='color:#ef4444;'>WhatsApp Bot Belum Aktif di Server</h2>
                    <p>Pastikan Anda sudah menjalankan bot di server via SSH/Terminal:</p>
                    <pre style='background:#1e293b;color:#f8fafc;padding:12px 20px;border-radius:8px;'>cd ~/rentspace/whatsapp-bot && npm start</pre>
                    <p style='font-size:13px;color:#64748b;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>
                    <a href='" . route('admin.settings', ['tab' => 'whatsapp']) . "' style='display:inline-block;margin-top:15px;padding:8px 16px;background:#0284c7;color:white;text-decoration:none;border-radius:6px;'>Kembali ke Settings</a>
                </div>";
            }
        })->name('admin.whatsapp-qr');

        // Printable Report Routes
        Route::get('/admin/report/monthly', [\App\Http\Controllers\Admin\ReportController::class, 'monthly'])->name('admin.report.monthly');
        Route::get('/admin/report/yearly', [\App\Http\Controllers\Admin\ReportController::class, 'yearly'])->name('admin.report.yearly');

        // Email Preview Routes
        Route::get('/admin/email-preview/{type}', function($type) {
            $rental = \App\Models\Rental::latest()->first();
            if (!$rental) return "Belum ada data rental di database untuk dijadikan contoh preview.";
            
            try {
                return match($type) {
                    'invoice' => new \App\Mail\NewOrderNotification($rental),
                    'confirmed' => new \App\Mail\PaymentConfirmedNotification($rental),
                    'reminder' => new \App\Mail\ReturnReminderNotification($rental),
                    'overdue' => new \App\Mail\OverdueNotification($rental),
                    default => abort(404),
                };
            } catch (\Exception $e) {
                return "Gagal me-render email: " . $e->getMessage();
            }
        })->name('admin.email-preview');
    });
    
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

// Customer Session Routes
Route::get('/masuk', CustomerLogin::class)->name('customer.login');
Route::get('/keluar', CustomerLogout::class)->name('customer.logout');

// Affiliate Public Routes
Route::get('/affiliate/login', AffiliateLogin::class)->name('affiliate.login');
Route::get('/affiliate/register', AffiliateRegister::class)->name('affiliate.register');
 
// Midtrans Webhook Route
Route::post('/official-midtrans-callback', [\App\Http\Controllers\MidtransWebhookController::class, 'handle']);

Route::get('/test-midtrans', function() {
    \Midtrans\Config::$serverKey = config('midtrans.server_key');
    return "Library Midtrans Aman!";
});

// Temporary route to clear cache on hosting - Delete after use
Route::get('/clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Semua cache berhasil dihapus!";
});
