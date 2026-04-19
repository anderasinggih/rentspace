<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Rental;
use Illuminate\Support\Str;

$rentals = Rental::whereNull('booking_code')->get();

echo "Backfilling " . $rentals->count() . " rentals...\n";

foreach ($rentals as $rental) {
    do {
        $code = strtoupper(Str::random(12));
    } while (Rental::where('booking_code', $code)->exists());

    $rental->update(['booking_code' => $code]);
    echo "Updated Rental ID {$rental->id} with code {$code}\n";
}

echo "Done!\n";
