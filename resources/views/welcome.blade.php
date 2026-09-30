@php
    \Carbon\Carbon::setLocale('id');
    $currentTime = now()->hour;
    $greeting = 'Halo Bos!';
    
    if ($currentTime >= 5 && $currentTime < 11) {
        $greeting = \App\Models\Setting::getVal('greeting_morning', 'Pagi Bos! ⚡️ Semangat harinya, jangan lupa bawa iPhone RentSpace buat momen spesialmu.');
    } elseif ($currentTime >= 11 && $currentTime < 15) {
        $greeting = \App\Models\Setting::getVal('greeting_day', 'Siang Bos! ☀️ Panas ya? Tetep tampil kece & profesional bareng iPhone dari RentSpace.');
    } elseif ($currentTime >= 15 && $currentTime < 18) {
        $greeting = \App\Models\Setting::getVal('greeting_afternoon', 'Sore Bos! ☁️ Purwokerto mulai sejuk nih, asik banget buat bikin konten cinematic.');
    } elseif ($currentTime >= 18 && $currentTime < 24) {
        $greeting = \App\Models\Setting::getVal('greeting_evening', 'Malam Bos! ✨ Butuh iPhone buat dinner atau event keren malam ini? Kami ready!');
    } else {
        $greeting = \App\Models\Setting::getVal('greeting_night', 'Masih bangun Bos? 🌙 Lagi nyari unit buat dipake besok ya? Langsung sikat!');
    }

    $customerSession = session('customer_session');
    $isCustomerLoggedIn = $customerSession
        && isset($customerSession['expires_at'])
        && now()->timestamp < $customerSession['expires_at'];

    $pendingOrders = collect();
    $closestActiveRental = null;
    $onlinePendingTotal = 0;
    $cashPendingTotal = 0;

    if ($isCustomerLoggedIn) {
        $pendingOrders = \App\Models\Rental::with('units')
            ->where('no_wa', $customerSession['no_wa'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $onlinePendingTotal = $pendingOrders->where('metode_pembayaran', '!=', 'cash')->count();
        $cashPendingTotal = $pendingOrders->where('metode_pembayaran', 'cash')->count();

        $closestActiveRental = \App\Models\Rental::where('no_wa', $customerSession['no_wa'])
            ->whereIn('status', ['paid', 'renting'])
            ->where('waktu_selesai', '>', now())
            ->orderBy('waktu_selesai', 'asc')
            ->first();
    }

    $statsTotalRentals = \App\Models\Rental::count();
    $statsTotalUsers = \App\Models\Rental::distinct('no_wa')->count('no_wa');
    $statsTotalHours = round(\App\Models\Rental::whereNotNull('waktu_mulai')->whereNotNull('waktu_selesai')->get()->sum(function ($r) {
        return \Carbon\Carbon::parse($r->waktu_mulai)->diffInHours(\Carbon\Carbon::parse($r->waktu_selesai));
    }));

    $statsTotalRentals = $statsTotalRentals > 0 ? $statsTotalRentals : 1;
    $statsTotalUsers = $statsTotalUsers > 0 ? $statsTotalUsers : 1;
    $statsTotalHours = $statsTotalHours > 0 ? $statsTotalHours : 24;

    $customerLtv = 0;
    $customerTier = null;
    if ($isCustomerLoggedIn) {
        $customerLtv = \App\Helpers\CustomerHelper::getLtv($customerSession['no_wa']);
        $customerTier = \App\Helpers\CustomerHelper::getTier($customerLtv);
    }
@endphp

@component('layouts.app', ['title' => config('app.name', 'RENT SPACE') . ' PURWOKERTO'])
    {{-- Hero Section & Showcase Carousel --}}
    @include('front.home.hero', [
        'greeting' => $greeting,
        'isCustomerLoggedIn' => $isCustomerLoggedIn,
        'closestActiveRental' => $closestActiveRental
    ])

    {{-- Stats Counters --}}
    @include('front.home.stats', [
        'statsTotalRentals' => $statsTotalRentals,
        'statsTotalUsers' => $statsTotalUsers,
        'statsTotalHours' => $statsTotalHours
    ])

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 w-full">
        {{-- Customer Session / Status Banner --}}
        @include('front.home.customer-session', [
            'isCustomerLoggedIn' => $isCustomerLoggedIn,
            'pendingOrders' => $pendingOrders,
            'onlinePendingTotal' => $onlinePendingTotal,
            'closestActiveRental' => $closestActiveRental,
            'customerTier' => $customerTier
        ])

        {{-- Active Promos --}}
        @include('front.home.promos')

        {{-- How To Order & Testimonials --}}
        @include('front.home.features')

        {{-- Catalog & Pricing List --}}
        @include('front.home.catalog')
    </div>
@endcomponent