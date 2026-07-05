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
        Route::get('/admin/ratings', \App\Livewire\Admin\RatingManager::class)->name('admin.ratings');
        Route::get('/admin/scan', \App\Livewire\Admin\QuickScan::class)->name('admin.scan');
        Route::get('/admin/radar', \App\Livewire\Admin\RadarDevices::class)->name('admin.radar');

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
Route::get('/booking/success/{booking_code}/og-image', function() {
    $width = 1200;
    $height = 630;
    $image = imagecreatetruecolor($width, $height);

    // Colors
    $bg = imagecolorallocate($image, 9, 9, 11); // zinc-950 #09090b
    $cardBg = imagecolorallocate($image, 24, 24, 27); // zinc-900 #18181b
    $border = imagecolorallocate($image, 39, 39, 42); // zinc-800 #27272a
    $white = imagecolorallocate($image, 255, 255, 255);
    $green = imagecolorallocate($image, 16, 185, 129); // emerald-500 #10b981
    $gray = imagecolorallocate($image, 113, 113, 122); // zinc-500

    imagefill($image, 0, 0, $bg);

    // Draw receipt box background (centered, 800x400)
    imagefilledrectangle($image, 200, 115, 1000, 515, $cardBg);
    imagerectangle($image, 200, 115, 1000, 515, $border);

    $font = resource_path('fonts/font.ttf');
    $drawText = function($image, $size, $x, $y, $color, $text) use ($font) {
        if (file_exists($font) && is_readable($font)) {
            try {
                imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                return;
            } catch (\Throwable $e) {
                // fallback
            }
        }
        imagestring($image, 5, $x, $y - 10, $text, $color);
    };

    // Draw Large Bold Header (Centered)
    // "INVOICE"
    $drawText($image, 56, 600 - 150, 270, $white, "INVOICE");
    
    // "RENT SPACE"
    $drawText($image, 44, 600 - 170, 360, $green, "RENT SPACE");

    // Subtitle
    $drawText($image, 18, 600 - 165, 430, $gray, "Penyewaan iPhone Purwokerto");

    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);

    return response($imageData)->header('Content-Type', 'image/png');
})->name('public.success.og-image');

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
