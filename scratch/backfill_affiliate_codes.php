<?php

use App\Models\AffiliatorProfile;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profiles = AffiliatorProfile::where('status', 'approved')->whereNull('referral_code')->get();

foreach ($profiles as $profile) {
    if ($profile->user) {
        $profile->referral_code = AffiliatorProfile::generateCode($profile->user->name);
        $profile->save();
        echo "Generated code {$profile->referral_code} for user {$profile->user->name}\n";
    }
}
