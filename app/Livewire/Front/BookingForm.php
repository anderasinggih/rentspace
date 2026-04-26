<?php

namespace App\Livewire\Front;

use App\Models\Unit;
use App\Models\Rental;
use App\Models\PricingRule;
use App\Mail\NewOrderNotification;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Livewire\Component;

class BookingForm extends Component
{
    public $nik, $nama, $alamat, $no_wa, $sosial_media;
    public $waktu_mulai, $waktu_selesai;
    public $unit_id; // Keeping for backward compat/initial select
    public $selected_unit_ids = [];
    public $available_units = [];
    public $subtotal = 0;
    public $potongan_diskon = 0;
    public $grand_total = 0;
    public $kode_unik = 0;
    public $agree = false;
    public $nikFoundMessage = null;
    public $nikFoundType = null;
    public $isNikVerified = false;
    public $selected_promo_ids = [];
    public $promo_code_input = '';
    public $available_promos = [];
    public $applied_promo_label = '';
    public $hari_bonus = 0; // extra days added by hari_gratis promo
    public $jam_bonus = 0;  // extra hours added by jam_gratis promo
    public $referral_code = '';
    public $promoManuallyChanged = false;
    public $unit_search = '';
    public $selected_category_id = null;
    public $schedule_available_unit_ids = [];
    public $categories_list = [];

    // Internal cache for the request lifecycle
    protected $all_pricing_rules = null;
    protected $fetched_selected_units = null;

    public function mount($unit_id = null)
    {
        // 1. Handle auto-selection of unit from URL (if still using specific links)
        if ($unit_id) {
            $this->selected_unit_ids = [(int) $unit_id];
            $this->checkAvailability();
        }

        // 2. Handle persistent customer session auto-fill
        $customerSession = session('customer_session');
        if ($customerSession && isset($customerSession['expires_at']) && now()->timestamp < $customerSession['expires_at']) {
            $this->nik = $customerSession['nik'];
            $this->no_wa = $customerSession['no_wa'];

            // Fetch name and address from latest rental
            $lastRental = Rental::where('nik', $this->nik)
                ->where('no_wa', $this->no_wa)
                ->latest()
                ->first();

            if ($lastRental) {
                $this->nama = $lastRental->nama;
                $this->alamat = $lastRental->alamat;
                $this->sosial_media = $lastRental->sosial_media;

                $firstName = explode(' ', $this->nama)[0];
                $this->nikFoundMessage = "Halo {$firstName}, data otomatis terisi dari sesi Anda.";
                $this->nikFoundType = 'success';
            } else {
                $this->nikFoundMessage = "Halo, NIK Anda terdeteksi. Silakan lengkapi sisa data.";
                $this->nikFoundType = 'success';
            }

            $this->isNikVerified = true;
        }

        // 3. Handle auto-apply of referral from Cookie or Session
        $ref = request()->cookie('affiliate_ref') ?? session('affiliate_ref');

        if ($ref) {
            $this->referral_code = $ref;
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }

        // 4. Pre-load categories
        $this->categories_list = \App\Models\Category::all();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'nik') {
            $this->nikFoundMessage = null;
            $this->nikFoundType = null;
            $this->isNikVerified = false;
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }

        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai'])) {
            $this->checkAvailability();
            $this->loadAvailablePromos();
        }

        if ($propertyName === 'selected_unit_ids') {
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }
        if ($propertyName === 'selected_promo_ids') {
            if (!is_array($this->selected_promo_ids)) {
                $this->selected_promo_ids = [];
            }
            $this->promoManuallyChanged = true;
            $this->validateStacking();
            $this->calculatePrice();
        }
        if ($propertyName === 'promo_code_input') {
            $this->loadAvailablePromos();
        }
        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai'])) {
            $this->calculatePrice();
        }
        if ($propertyName === 'referral_code') {
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }
        if (in_array($propertyName, ['selected_category_id', 'unit_search'])) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability()
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai)
            return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);

        if ($end->lte($start)) {
            $this->addError('waktu_selesai', 'Harus setelah waktu mulai');
            $this->available_units = [];
            return;
        }

        // 1. BASE Availability (Used for the main catalog list)
        // We check only the range the user is actually picking [start, end]
        $this->schedule_available_unit_ids = Unit::query()->where('is_active', true)
            ->whereDoesntHave('rentals', function ($query) use ($start, $end) {
                $query->whereIn('status', ['pending', 'paid'])
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('waktu_mulai', [$start, $end])
                            ->orWhereBetween('waktu_selesai', [$start, $end])
                            ->orWhere(function ($q2) use ($start, $end) {
                                $q2->where('waktu_mulai', '<=', $start)
                                    ->where('waktu_selesai', '>=', $end);
                            });
                    });
            })->pluck('id')->toArray();

        // 2. EFFECTIVE Availability (Check bonus time for already selected units)
        $hBonus = 0;
        $jBonus = 0;
        if (!empty($this->selected_promo_ids)) {
            $appliedRules = PricingRule::whereIn('id', $this->selected_promo_ids)->get();
            foreach ($appliedRules as $rule) {
                if ($rule->tipe === 'hari_gratis') $hBonus += (int)$rule->value;
                if ($rule->tipe === 'jam_gratis') $jBonus += (int)$rule->value;
            }
        }

        if (($hBonus > 0 || $jBonus > 0) && !empty($this->selected_unit_ids)) {
            $effectiveEnd = $end->copy()->addDays($hBonus)->addHours($jBonus);
            
            // Check if any selected unit has a conflict in the BONUS period [end, effectiveEnd]
            $clashingUnitIds = Unit::whereIn('id', $this->selected_unit_ids)
                ->whereHas('rentals', function ($query) use ($end, $effectiveEnd) {
                    $query->whereIn('status', ['pending', 'paid'])
                        ->where(function ($q) use ($end, $effectiveEnd) {
                            $q->whereBetween('waktu_mulai', [$end, $effectiveEnd])
                                ->orWhereBetween('waktu_selesai', [$end, $effectiveEnd])
                                ->orWhere(function ($q2) use ($end, $effectiveEnd) {
                                    $q2->where('waktu_mulai', '<=', $end)
                                        ->where('waktu_selesai', '>=', $effectiveEnd);
                                });
                        });
                })->pluck('id')->toArray();

            if (!empty($clashingUnitIds)) {
                $names = Unit::whereIn('id', $clashingUnitIds)->pluck('seri')->implode(', ');
                $this->addError('selected_promo_ids', "Promo bonus tidak bisa digunakan untuk unit ($names) karena bentrok dengan jadwal lain.");
            }
        }

        // 3. Update Available Units Display (Based on BASE range)
        $this->available_units = Unit::query()->with('category')
            ->whereIn('id', $this->schedule_available_unit_ids)
            ->when($this->selected_category_id, function ($q) {
                $q->where('category_id', $this->selected_category_id);
            })
            ->when($this->unit_search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('seri', 'like', '%' . $this->unit_search . '%')
                        ->orWhere('warna', 'like', '%' . $this->unit_search . '%')
                        ->orWhere('memori', 'like', '%' . $this->unit_search . '%');
                });
            })
            ->get();

        // 4. Remove selected units ONLY if they are not available in the BASE range
        $this->selected_unit_ids = array_values(array_intersect($this->selected_unit_ids, $this->schedule_available_unit_ids));

        $this->calculatePrice();
    }

    public function loadAvailablePromos()
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai || empty($this->selected_unit_ids)) {
            $this->available_promos = [];
            return;
        }

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);
        $diffInHours = $start->diffInHours($end);
        $days = floor($diffInHours / 24);

        $now = Carbon::now();

        // 1. Fetch rules (Cached for 10 minutes)
        if ($this->all_pricing_rules === null) {
            $this->all_pricing_rules = \Illuminate\Support\Facades\Cache::remember('active_pricing_rules_v2', 600, function () use ($now) {
                return PricingRule::where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', $now->format('Y-m-d'));
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $now->format('Y-m-d'));
                    })
                    ->withCount(['rentals' => function($q) {
                        $q->where('status', '!=', 'cancelled');
                    }])
                    ->get();
            });
        }

        // 2. Pre-calculate values used inside filter to avoid per-item DB queries
        $isAffiliateAuth = auth()->check() && auth()->user()->role === 'affiliator';
        $isAffiliateNik = false;
        if (!empty($this->nik)) {
            // Cache this check for the current request
            $isAffiliateNik = \App\Models\AffiliatorProfile::where('nik', $this->nik)
                ->where('status', 'approved')
                ->exists();
        }

        $isEligibleForAffiliatePromos = $isAffiliateAuth || $isAffiliateNik;

        $rules = $this->all_pricing_rules;

        // 3. Filter and Map
        $this->available_promos = $rules->filter(function ($rule) use ($isEligibleForAffiliatePromos) {
            if ($rule->is_affiliate_only && !$isEligibleForAffiliatePromos) return false;
            if ($rule->requires_referral && empty($this->referral_code)) return false;

            if ($rule->affiliate_code) {
                return $this->referral_code && strtoupper(trim($this->referral_code)) === strtoupper(trim($rule->affiliate_code));
            }

            if ($rule->is_hidden) {
                if ($rule->requires_referral || $rule->is_affiliate_only || $rule->affiliate_code) return true;
                $codeMatch = $this->promo_code_input && strtoupper(trim($this->promo_code_input)) === strtoupper(trim($rule->kode_promo));
                return $codeMatch || in_array($rule->id, $this->selected_promo_ids);
            }
            return true;
        })->map(function ($rule) use ($days, $diffInHours, $start, $end) {
            $durasiTerkonversi = $rule->syarat_tipe_durasi === 'hari' ? $days : $diffInHours;
            $is_eligible = !$rule->syarat_minimal_durasi || $durasiTerkonversi >= $rule->syarat_minimal_durasi;
            $ineligible_reason = null;

            // Check for Bonus Time Clash if units are already selected
            if ($is_eligible && ($rule->tipe === 'hari_gratis' || $rule->tipe === 'jam_gratis') && !empty($this->selected_unit_ids)) {
                $h = $rule->tipe === 'hari_gratis' ? (int)$rule->value : 0;
                $j = $rule->tipe === 'jam_gratis' ? (int)$rule->value : 0;
                $effectiveEnd = $end->copy()->addDays($h)->addHours($j);

                $conflict = Unit::whereIn('id', $this->selected_unit_ids)
                    ->whereHas('rentals', function ($q) use ($end, $effectiveEnd) {
                        $q->whereIn('status', ['pending', 'paid'])
                          ->where(function ($qq) use ($end, $effectiveEnd) {
                              $qq->whereBetween('waktu_mulai', [$end, $effectiveEnd])
                                 ->orWhereBetween('waktu_selesai', [$end, $effectiveEnd])
                                 ->orWhere(function ($qq2) use ($end, $effectiveEnd) {
                                     $qq2->where('waktu_mulai', '<=', $end)
                                         ->where('waktu_selesai', '>=', $effectiveEnd);
                                 });
                          });
                    })->exists();

                if ($conflict) {
                    $is_eligible = false;
                    $ineligible_reason = 'Bentrok dengan jadwal lain';
                }
            }

            // Check Quota Limit
            if ($is_eligible && $rule->usage_limit !== null) {
                if ($rule->rentals_count >= $rule->usage_limit) {
                    $is_eligible = false;
                    $ineligible_reason = 'Kuota promo sudah habis';
                }
            }

            // Auto-select if it's an affiliate-specific promo and eligible (no clash)
            $isAffiliatePromo = $rule->affiliate_code || $rule->requires_referral || $rule->is_affiliate_only;
            if ($isAffiliatePromo && $is_eligible && !in_array($rule->id, $this->selected_promo_ids) && !$this->promoManuallyChanged) {
                $this->selected_promo_ids[] = $rule->id;
            }

            return array_merge($rule->toArray(), [
                'is_eligible' => $is_eligible,
                'ineligible_reason' => $ineligible_reason
            ]);
        })->values()->toArray();
    }

    public function validateStacking()
    {
        if (empty($this->selected_promo_ids))
            return;

        $lastSelectedId = end($this->selected_promo_ids);

        if ($this->all_pricing_rules === null) {
            $this->loadAvailablePromos();
        }

        $lastSelectedRule = $this->all_pricing_rules->firstWhere('id', $lastSelectedId);

        if (!$lastSelectedRule)
            return;

        if (!$lastSelectedRule->can_stack) {
            // If the newly selected one is NOT stackable, clear others and keep only this one
            $this->selected_promo_ids = [$lastSelectedId];
        } else {
            // If the newly selected one IS stackable, remove any existing non-stackable ones
            $this->selected_promo_ids = collect($this->selected_promo_ids)
                ->filter(function ($id) {
                    $r = $this->all_pricing_rules->firstWhere('id', $id);
                    return $r && $r->can_stack;
                })->toArray();
        }
    }

    public function checkCode()
    {
        if (empty($this->promo_code_input)) {
            $this->addError('promo_code_input', 'Silakan masukkan kode terlebih dahulu.');
            return;
        }

        $input = strtoupper(trim($this->promo_code_input));
        $found = false;
        $message = '';

        // 1. Check if it's a Referral Code
        $affiliate = \App\Models\AffiliatorProfile::where('referral_code', $input)
            ->where('status', 'approved')
            ->first();

        if ($affiliate) {
            $this->referral_code = $input;
            $this->loadAvailablePromos();
            $this->calculatePrice();
            $found = true;
            $message = 'Kode Referral Aktif!';
        }

        // 2. Check if it's a Promo Code (Voucher)
        $this->loadAvailablePromos();
        foreach ($this->available_promos as $p) {
            if (isset($p['kode_promo']) && strtoupper(trim($p['kode_promo'])) === $input) {
                if ($p['is_eligible'] && !in_array($p['id'], $this->selected_promo_ids)) {
                    $this->selected_promo_ids[] = $p['id'];
                    $this->validateStacking();
                    $this->calculatePrice();
                    $found = true;
                    $message = 'Kode Promo Berhasil!';
                }
            }
        }

        if ($found) {
            session()->flash('promo_message', $message);
        } else {
            $this->addError('promo_code_input', 'Kode tidak ditemukan atau syarat belum terpenuhi.');
        }
    }

    public function calculatePrice()
    {
        if (empty($this->selected_unit_ids) || !$this->waktu_mulai || !$this->waktu_selesai) {
            $this->subtotal = 0;
            $this->grand_total = 0;
            $this->hari_bonus = 0;
            $this->jam_bonus = 0;
            return;
        }

        if ($this->fetched_selected_units === null) {
            $this->fetched_selected_units = Unit::whereIn('id', $this->selected_unit_ids)->get();
        }
        $units = $this->fetched_selected_units;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);

        $diffInHours = max(1, $start->diffInHours($end));
        $days = floor($diffInHours / 24);
        $remainingHours = $diffInHours % 24;

        $this->subtotal = 0;
        foreach ($units as $unit) {
            $this->subtotal += ($days * $unit->harga_per_hari) + ($remainingHours * $unit->harga_per_jam);
        }

        $this->potongan_diskon = 0;
        $this->hari_bonus = 0;
        $this->jam_bonus = 0;
        $this->applied_promo_label = '';

        if (!empty($this->selected_promo_ids)) {
            if ($this->all_pricing_rules === null) {
                $this->loadAvailablePromos();
            }
            $rules = collect($this->all_pricing_rules)->whereIn('id', $this->selected_promo_ids);
            $labels = [];

            foreach ($rules as $rule) {
                $labels[] = $rule->nama_promo;

                if ($rule->tipe === 'diskon_persen') {
                    $this->potongan_diskon += $this->subtotal * ($rule->value / 100);
                } elseif ($rule->tipe === 'hari_gratis') {
                    $this->hari_bonus += (int) $rule->value;
                } elseif ($rule->tipe === 'jam_gratis') {
                    $this->jam_bonus += (int) $rule->value;
                } elseif ($rule->tipe === 'diskon_nominal') {
                    $this->potongan_diskon += $rule->value;
                } elseif ($rule->tipe === 'fix_price') {
                    // Fix price is tricky with multiple. We'll take the lowest fix price or cap the discount.
                    // Usually fix_price shouldn't be stackable, but if it is, we treat it as a discount off subtotal.
                    $discountFromFix = max(0, $this->subtotal - $rule->value);
                    $this->potongan_diskon += $discountFromFix;
                } elseif ($rule->tipe === 'cashback') {
                    // Cashback doesn't affect grand_total right now, maybe just label? 
                    // Let's treat it as discount if used on front.
                }
            }
            $this->applied_promo_label = implode(', ', $labels);
        }

        // Prevent negative total
        if ($this->potongan_diskon > $this->subtotal) {
            $this->potongan_diskon = $this->subtotal;
        }

        if ($this->kode_unik === 0) {
            $this->kode_unik = rand(100, 999);
        }

        $this->grand_total = $this->subtotal - $this->potongan_diskon + $this->kode_unik;
    }

    public function submit()
    {
        $this->validate([
            'nik' => 'required|numeric',
            'nama' => 'required',
            'no_wa' => 'required|numeric',
            'sosial_media' => 'required',
            'alamat' => 'required',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
            'selected_unit_ids' => 'required|array|min:1',
            'agree' => 'accepted',
        ], [
            'nik.numeric' => 'NIK harus berupa angka.',
            'no_wa.numeric' => 'Nomor WhatsApp harus berupa angka.',
            'agree.accepted' => 'Anda wajib menyetujui syarat & ketentuan penyewaan sebelum melanjutkan.',
        ]);

        $this->checkAvailability();
        foreach ($this->selected_unit_ids as $sid) {
            if (!$this->available_units->contains('id', $sid)) {
                $this->addError('selected_unit_ids', 'Beberapa unit tidak tersedia di slot waktu yang Anda pilih.');
                return;
            }
        }

        // Recalculate duration for price snapshot
        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);
        $diffInHours = max(1, $start->diffInHours($end));
        $days = floor($diffInHours / 24);
        $remainingHours = $diffInHours % 24;

        // If hari_gratis promo applied, extend waktu_selesai
        $finalWaktuSelesai = $this->waktu_selesai;
        if ($this->hari_bonus > 0) {
            $finalWaktuSelesai = Carbon::parse($this->waktu_selesai)->addDays($this->hari_bonus)->format('Y-m-d\TH:i');
        }
        if ($this->jam_bonus > 0) {
            $finalWaktuSelesai = Carbon::parse($finalWaktuSelesai)->addHours($this->jam_bonus)->format('Y-m-d\TH:i');
        }

        $rental = Rental::create([
            'unit_id' => $this->selected_unit_ids[0] ?? null, // Backward compatibility
            'nik' => $this->nik,
            'nama' => strtoupper($this->nama),
            'alamat' => strtoupper($this->alamat),
            'sosial_media' => $this->sosial_media,
            'no_wa' => $this->no_wa,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $finalWaktuSelesai,
            'subtotal_harga' => $this->subtotal,
            'potongan_diskon' => $this->potongan_diskon,
            'applied_promo_name' => $this->applied_promo_label ?: null,
            'applied_promo_id' => !empty($this->selected_promo_ids) ? reset($this->selected_promo_ids) : null, // Store primary promo ID
            'hari_bonus' => $this->hari_bonus,
            'jam_bonus' => $this->jam_bonus,
            'kode_unik_pembayaran' => $this->kode_unik,
            'grand_total' => $this->grand_total,
            'status' => 'pending',
            'metode_pembayaran' => 'online', // Paksa online biar gak kena default qris dari DB
            'affiliate_code' => $this->referral_code ?: null,
            'affiliator_id' => $this->referral_code ? (\App\Models\AffiliatorProfile::where('referral_code', strtoupper($this->referral_code))->first()->user_id ?? null) : null,
        ]);

        // Create customer session for auto-login/auto-persistence
        session(['customer_session' => [
            'nik' => $this->nik,
            'no_wa' => $this->no_wa,
            'expires_at' => now()->addDays(7)->timestamp,
        ]]);

        // Record ownership in session
        $owned = session('owned_bookings', []);
        $owned[] = $rental->booking_code;
        session(['owned_bookings' => $owned]);

        // Create rental items
        foreach ($this->selected_unit_ids as $uid) {
            $u = Unit::find($uid);
            $uPrice = ($days * $u->harga_per_hari) + ($remainingHours * $u->harga_per_jam);
            \App\Models\RentalItem::create([
                'rental_id' => $rental->id,
                'unit_id' => $uid,
                'price_snapshot' => $uPrice
            ]);
        }

        $this->dispatch('booking-submitted');

        // Send Email Notification to Admin
        try {
            $adminEmail = env('ADMIN_EMAIL');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new NewOrderNotification($rental));
            }
        } catch (\Exception $e) {
            // Silently fail to not block customer redirect
            \Illuminate\Support\Facades\Log::error("Email failed: " . $e->getMessage());
        }

        return redirect()->route('public.payment', $rental->booking_code);
    }

    public function checkNik()
    {
        $this->validate([
            'nik' => 'required|numeric'
        ], [
            'nik.required' => 'Masukkan NIK terlebih dahulu untuk mengecek data.',
            'nik.numeric' => 'NIK harus berupa angka.'
        ]);

        $lastRental = Rental::where('nik', $this->nik)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRental) {
            $this->nama = $lastRental->nama;
            $this->no_wa = $lastRental->no_wa;
            $this->alamat = $lastRental->alamat;
            $this->sosial_media = $lastRental->sosial_media;
            $firstName = explode(' ', $this->nama)[0];
            $this->nikFoundMessage = "Halo {$firstName}, data Anda berhasil ditemukan!";
            $this->nikFoundType = 'success';
            $this->isNikVerified = true;
        } else {
            $this->nikFoundMessage = 'NIK belum pernah digunakan, silakan isi data baru.';
            $this->nikFoundType = 'warning';
            $this->isNikVerified = false;
        }
    }

    public function render()
    {
        $unitPrices = \App\Models\Unit::select('id', 'harga_per_hari', 'harga_per_jam', 'seri', 'warna', 'memori')
            ->get()
            ->mapWithKeys(fn($u) => [
            $u->id => [
                'day' => (int) $u->harga_per_hari,
                'hour' => (int) $u->harga_per_jam,
                'seri' => $u->seri,
                'warna' => $u->warna,
                'memori' => $u->memori
            ]
        ]);

        return view('livewire.front.booking-form', [
            'unitPricesJson' => $unitPrices->toJson()
        ])->layout('layouts.app');
    }
}
