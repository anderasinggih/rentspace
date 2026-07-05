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
Route::get('/booking/success/{booking_code}/og-image', function($booking_code) {
    $rental = \App\Models\Rental::with('units')
        ->where('booking_code', $booking_code)
        ->firstOrFail();

    $unit = $rental->units->pluck('nama_unit')->join(', ');
    if (strlen($unit) > 30) {
        $unit = substr($unit, 0, 27) . '...';
    }
    $tanggal = $rental->waktu_mulai->format('d M Y') . ' - ' . $rental->waktu_selesai->format('d M Y');

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

    // Draw receipt box background (centered, 500x450)
    imagefilledrectangle($image, 350, 60, 850, 510, $cardBg);
    imagerectangle($image, 350, 60, 850, 510, $border);

    $font = storage_path('app/font.ttf');

    // Draw Header
    imagettftext($image, 26, 0, 600 - 95, 110, $green, $font, "RENT SPACE");
    imagettftext($image, 12, 0, 600 - 60, 140, $gray, $font, "INVOICE RENTAL");
    
    // Draw booking code
    $code = "#" . $rental->booking_code;
    imagettftext($image, 18, 0, 600 - (strlen($code) * 7), 180, $white, $font, $code);

    // Divider line
    imageline($image, 390, 205, 810, 205, $border);

    // Customer details
    imagettftext($image, 11, 0, 390, 235, $gray, $font, "NAMA PENYEWA");
    
    $nama = strtoupper($rental->nama);
    if (strlen($nama) > 25) {
        $nama = substr($nama, 0, 22) . '...';
    }
    imagettftext($image, 15, 0, 390, 265, $white, $font, $nama);

    imagettftext($image, 11, 0, 390, 310, $gray, $font, "UNIT SEWA");
    imagettftext($image, 14, 0, 390, 340, $white, $font, $unit);

    imagettftext($image, 11, 0, 390, 385, $gray, $font, "TANGGAL SEWA");
    imagettftext($image, 13, 0, 390, 410, $white, $font, $tanggal);

    // Divider 2
    imageline($image, 390, 435, 810, 435, $border);

    // Total
    imagettftext($image, 12, 0, 390, 475, $green, $font, "TOTAL BAYAR");
    $totalStr = "Rp " . number_format($rental->grand_total, 0, ',', '.');
    imagettftext($image, 18, 0, 810 - (strlen($totalStr) * 12), 475, $green, $font, $totalStr);

    // Status Badge
    $statusStr = strtoupper($rental->status);
    $statusBg = imagecolorallocate($image, 82, 82, 91); // zinc-600 default
    if ($rental->status === 'paid' || $rental->status === 'completed') {
        $statusBg = imagecolorallocate($image, 6, 95, 70); // dark green
    } elseif ($rental->status === 'pending') {
        $statusBg = imagecolorallocate($image, 146, 64, 14); // dark amber
    } elseif ($rental->status === 'cancelled') {
        $statusBg = imagecolorallocate($image, 153, 27, 27); // dark red
    }
    imagefilledrectangle($image, 530, 525, 670, 555, $statusBg);
    imagettftext($image, 10, 0, 600 - (strlen($statusStr) * 4.5), 544, $white, $font, $statusStr);

    // Footer Text
    imagettftext($image, 12, 0, 600 - 80, 595, $gray, $font, "rentspacepurwokerto.my.id");

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
