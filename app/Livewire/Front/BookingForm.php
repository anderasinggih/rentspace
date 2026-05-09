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
    public $nik, $nama, $email, $alamat, $no_wa, $sosial_media;
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
    public $member_checked = false;
    public $loyalty_rule_id = null;
    public $loyalty_discount_value = 0;
    public $loyalty_discount_type = null;

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
                $this->email = $lastRental->email;
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
            $this->member_checked = true;
            $this->checkLoyaltyBenefits();
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
            $this->member_checked = false;
            $this->loyalty_rule_id = null;
            $this->loyalty_discount_value = 0;
            $this->loyalty_discount_type = null;
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }

        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai'])) {
            $this->checkAvailability();
            $this->loadAvailablePromos();
        }

        if ($propertyName === 'selected_unit_ids') {
            $this->checkAvailability();
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
            $this->resetErrorBag('promo_code_input');
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

        if ($propertyName === 'email') {
            $this->validateOnly('email', [
                'email' => 'required|email'
            ]);
        }
    }

    public function checkAvailability()
    {
        $this->resetErrorBag('waktu_selesai');
        if (!$this->waktu_mulai || !$this->waktu_selesai)
            return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);

        if ($end->lte($start)) {
            $this->addError('waktu_selesai', 'Harus setelah waktu mulai');
            $this->available_units = collect();
            return;
        }

        // 1. Calculate Availability Status for ALL units
        $units = Unit::query()->where('is_active', true)
            ->with(['category', 'rentals' => function($q) use ($start, $end) {
                $q->whereIn('status', ['pending', 'paid', 'renting', 'pending_confirmation'])
                  ->where(function($qq) use ($start, $end) {
                      $qq->whereBetween('waktu_mulai', [$start, $end])
                         ->orWhereBetween('waktu_selesai', [$start, $end])
                         ->orWhere(function($qq2) use ($start, $end) {
                             $qq2->where('waktu_mulai', '<=', $start)
                                ->where('waktu_selesai', '>=', $end);
                         });
                  })
                  ->orderBy('waktu_mulai', 'asc');
            }])
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

        $this->schedule_available_unit_ids = [];
        foreach ($units as $unit) {
            $conflicts = $unit->rentals;
            
            if ($conflicts->isEmpty()) {
                $unit->availability_status = 'ready';
                $unit->availability_label = 'Ready Sekarang';
                $this->schedule_available_unit_ids[] = $unit->id;
            } else {
                // Check if start time is occupied
                $startOccupied = $conflicts->contains(function($r) use ($start) {
                    return $start->gte($r->waktu_mulai) && $start->lt($r->waktu_selesai);
                });

                if (!$startOccupied) {
                    // Ready from start, but conflict starts later
                    $firstConflict = $conflicts->where('waktu_mulai', '>', $start)->first();
                    $unit->availability_status = 'partial_until';
                    $unit->availability_label = 'Ready s/d ' . Carbon::parse($firstConflict->waktu_mulai)->translatedFormat('d M, H:i');
                } else {
                    // Start is occupied, check if it becomes free before end
                    $lastConflictInPeriod = $conflicts->where('waktu_selesai', '<', $end)->sortByDesc('waktu_selesai')->first();
                    
                    if ($lastConflictInPeriod) {
                        $unit->availability_status = 'partial_from';
                        $unit->availability_label = 'Ready mulai ' . Carbon::parse($lastConflictInPeriod->waktu_selesai)->translatedFormat('d M, H:i');
                    } else {
                        $unit->availability_status = 'full';
                        $unit->availability_label = 'Full Booked';
                    }
                }
            }
        }

        $this->available_units = $units->sortBy(function($unit) {
            $status = $unit->availability_status ?? 'full';
            if ($status === 'ready') return 1;
            if ($status === 'partial_until' || $status === 'partial_from') return 2;
            return 3;
        });

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

        // 1. Fetch rules (Global active rules cached for 10 minutes)
        if ($this->all_pricing_rules === null) {
            $this->all_pricing_rules = \Illuminate\Support\Facades\Cache::remember('active_pricing_rules_global', 600, function () {
                return PricingRule::where('is_active', true)
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
        $this->available_promos = $rules->filter(function ($rule) use ($isEligibleForAffiliatePromos, $start) {
            // Filter by Date (Check against Rental Start Date, not current time)
            $startDateMatch = $rule->start_date === null || $rule->start_date <= $start->format('Y-m-d');
            $endDateMatch = $rule->end_date === null || $rule->end_date >= $start->format('Y-m-d');
            if (!$startDateMatch || !$endDateMatch) return false;

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
            $minDurasi = $rule->syarat_minimal_durasi;
            $is_eligible = ($minDurasi === null || $minDurasi === '') || $durasiTerkonversi >= (float)$minDurasi;
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

    public function checkMember()
    {
        $this->resetErrorBag('nik');
        if (!$this->nik) return;

        // Force 16 digits
        if (strlen($this->nik) !== 16) {
            $this->addError('nik', 'NIK harus terdiri dari 16 digit.');
            return;
        }
        
        $ltv = \App\Helpers\CustomerHelper::getLtv($this->nik);
        
        if ($ltv <= 0) {
            $this->addError('nik', 'NIK belum terdaftar sebagai member.');
            $this->member_checked = false;
            return;
        }

        $tier = \App\Helpers\CustomerHelper::getTier($ltv);
        
        // Find if name exists for friendly greeting
        $lastRental = Rental::where('nik', $this->nik)->latest()->first();
        $this->nama = $lastRental->nama ?? '';
        
        $this->member_checked = true;
        $this->checkLoyaltyBenefits();
        
        if ($lastRental) {
            $firstName = explode(' ', $this->nama)[0];
            $this->nikFoundMessage = "Halo {$firstName}, promo spesial member {$tier->label} Anda sudah aktif!";
        } else {
            $this->nikFoundMessage = "Promo spesial member {$tier->label} Anda sudah aktif!";
        }
        $this->nikFoundType = 'success';
        
        $this->calculatePrice();
    }

    public function checkLoyaltyBenefits()
    {
        if (!$this->nik) return;
        
        $ltv = \App\Helpers\CustomerHelper::getLtv($this->nik);
        $tier = \App\Helpers\CustomerHelper::getTier($ltv);
        
        // Find applicable loyalty rule for this tier
        $rule = PricingRule::where('target_loyalty_tier', $tier->label)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latest()
            ->first();
            
        if ($rule) {
            $this->loyalty_rule_id = $rule->id;
            $this->loyalty_discount_value = $rule->value;
            $this->loyalty_discount_type = $rule->tipe;
        } else {
            $this->loyalty_rule_id = null;
            $this->loyalty_discount_value = 0;
            $this->loyalty_discount_type = null;
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

        // 1. Apply Loyalty Discount (Auto-apply)
        if ($this->loyalty_rule_id && $this->loyalty_discount_value > 0) {
            if ($this->loyalty_discount_type === 'diskon_persen') {
                $this->potongan_diskon += $this->subtotal * ($this->loyalty_discount_value / 100);
            } elseif ($this->loyalty_discount_type === 'diskon_nominal') {
                $this->potongan_diskon += $this->loyalty_discount_value;
            } elseif ($this->loyalty_discount_type === 'fix_price') {
                $unitCount = count($this->selected_unit_ids);
                $targetTotal = $this->loyalty_discount_value * $unitCount;
                $this->potongan_diskon += max(0, $this->subtotal - $targetTotal);
            }
        }

        // 2. Apply Voucher/Promo Codes
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
                    // Fix price is applied per unit to prevent massive losses on multi-unit rentals
                    $unitCount = count($this->selected_unit_ids);
                    $targetTotal = $rule->value * $unitCount;
                    $discountFromFix = max(0, $this->subtotal - $targetTotal);
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
            'email' => 'required|email',
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
        if ($this->getErrorBag()->any()) return;

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
            'email' => strtolower($this->email),
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

        // --- PUSH NOTIFICATION KE ADMIN (PESANAN BARU) ---
        try {
            \App\Services\OneSignalService::sendToAdmins(
                "🆕 Pesanan Baru: " . strtoupper($this->nama) . " membooking unit (Rp " . number_format($this->grand_total, 0, ',', '.') . ")",
                "🔔 PESANAN MASUK",
                route('admin.monitoring')
            );
        } catch (\Exception $e) { }
        
        // Attach all selected promos for accurate usage tracking (including stacked ones)
        if (!empty($this->selected_promo_ids)) {
            $rental->appliedPromos()->attach($this->selected_promo_ids);
        }

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
            $this->email = $lastRental->email;
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

    public function getTierProperty()
    {
        if (!$this->nik) return null;
        $ltv = \App\Helpers\CustomerHelper::getLtv($this->nik);
        if ($ltv <= 0) return null;
        return \App\Helpers\CustomerHelper::getTier($ltv);
    }

    public function getLoyaltyDiscountedUnitPricesProperty()
    {
        $prices = [];
        // We use all units that are visible in the form
        $units = Unit::all();
        
        foreach ($units as $unit) {
            $original_hari = $unit->harga_per_hari;
            $original_jam = $unit->harga_per_jam;
            
            $discounted_hari = $original_hari;
            $discounted_jam = $original_jam;
            
            if ($this->loyalty_rule_id && $this->loyalty_discount_value > 0) {
                if ($this->loyalty_discount_type === 'diskon_persen') {
                    $discounted_hari = $original_hari * (1 - $this->loyalty_discount_value / 100);
                    $discounted_jam = $original_jam * (1 - $this->loyalty_discount_value / 100);
                } elseif ($this->loyalty_discount_type === 'diskon_nominal') {
                    // Usually nominal is for the whole order, but for display we can show it as a hint or split it
                    // For now, let's just stick to percent/fix price for unit display
                } elseif ($this->loyalty_discount_type === 'fix_price') {
                    $discounted_hari = $this->loyalty_discount_value;
                    // For fix_price hourly, we might need more logic, but usually it's daily
                }
            }
            
            $prices[$unit->id] = [
                'hari' => $discounted_hari,
                'jam' => $discounted_jam,
                'original_hari' => $original_hari,
                'original_jam' => $original_jam,
                'has_discount' => $discounted_hari < $original_hari
            ];
        }
        
        return $prices;
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
