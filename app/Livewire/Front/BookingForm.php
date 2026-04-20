<?php

namespace App\Livewire\Front;

use App\Models\Unit;
use App\Models\Rental;
use App\Models\PricingRule;
use Carbon\Carbon;
use Livewire\Component;

class BookingForm extends Component
{
    public $nik, $nama, $alamat, $no_wa;
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

    public function mount($unit_id = null)
    {
        // 1. Handle auto-selection of unit from URL (if still using specific links)
        if ($unit_id) {
            $this->selected_unit_ids = [(int)$unit_id];
            $this->checkAvailability();
        }

        // 2. Handle auto-apply of referral from Cookie or Session
        $ref = request()->cookie('affiliate_ref') ?? session('affiliate_ref');
        
        if ($ref) {
            $this->referral_code = $ref;
            $this->loadAvailablePromos();
            $this->calculatePrice();
        }
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
        if ($propertyName === 'nama') {
            $this->nama = strtoupper($this->nama);
        }
        if ($propertyName === 'alamat') {
            $this->alamat = strtoupper($this->alamat);
        }

        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai', 'selected_unit_ids'])) {
            $this->checkAvailability();
            $this->loadAvailablePromos();
        }
        if ($propertyName === 'selected_promo_ids') {
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
    }

    public function checkAvailability()
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai)
            return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);

        if ($end->lte($start)) {
            $this->addError('waktu_selesai', 'Waktu selesai harus setelah waktu mulai');
            $this->available_units = [];
            return;
        }

        $this->available_units = Unit::query()->with('category')->where('is_active', true)
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
            })->get();

        // Remove units that are no longer available
        $this->selected_unit_ids = array_intersect($this->selected_unit_ids, $this->available_units->pluck('id')->toArray());

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
        $rules = PricingRule::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now->format('Y-m-d'));
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now->format('Y-m-d'));
            })
            ->get();

        $this->available_promos = $rules->filter(function ($rule) {
            // Check if it's an affiliate-only promo
            if ($rule->is_affiliate_only) {
                $isAffiliateAuth = auth()->check() && auth()->user()->role === 'affiliator';
                $isAffiliateNik = !empty($this->nik) && \App\Models\AffiliatorProfile::where('nik', $this->nik)->where('status', 'approved')->exists();
                
                if (!$isAffiliateAuth && !$isAffiliateNik) return false;
            }

            // Check if it requires a referral code
            if ($rule->requires_referral) {
                if (empty($this->referral_code)) return false;
            }

            // Existing logic for affiliate_code specific promos
            if ($rule->affiliate_code) {
                $refMatch = $this->referral_code && strtoupper(trim($this->referral_code)) === strtoupper(trim($rule->affiliate_code));
                return $refMatch;
            }

            // If hidden, only show if:
            // 1. It's tied to an affiliate/referral constraint (handled above)
            // 2. OR code matches
            // 3. OR it's already selected
            if ($rule->is_hidden) {
                if ($rule->requires_referral || $rule->is_affiliate_only || $rule->affiliate_code) {
                    // These are allowed if the previous checks passed
                    return true;
                }
                $codeMatch = $this->promo_code_input && strtoupper(trim($this->promo_code_input)) === strtoupper(trim($rule->kode_promo));
                $isSelected = in_array($rule->id, $this->selected_promo_ids);
                return $codeMatch || $isSelected;
            }
            return true;
        })->map(function ($rule) use ($days, $diffInHours) {
            $durasiTerkonversi = $rule->syarat_tipe_durasi === 'hari' ? $days : $diffInHours;
            $is_eligible = !$rule->syarat_minimal_durasi || $durasiTerkonversi >= $rule->syarat_minimal_durasi;

            // Auto-select if it's an affiliate-specific promo and eligible (only if user hasn't manually changed promos)
            $isAffiliatePromo = $rule->affiliate_code || $rule->requires_referral || $rule->is_affiliate_only;
            if ($isAffiliatePromo && $is_eligible && !in_array($rule->id, $this->selected_promo_ids) && !$this->promoManuallyChanged) {
                $this->selected_promo_ids[] = $rule->id;
                $this->validateStacking(); // Ensure we don't break stacking rules
            }

            return array_merge($rule->toArray(), [
                'is_eligible' => $is_eligible
            ]);
        })->values()->toArray();
    }

    public function validateStacking()
    {
        if (empty($this->selected_promo_ids))
            return;

        $lastSelectedId = end($this->selected_promo_ids);
        $lastSelectedRule = PricingRule::find($lastSelectedId);

        if (!$lastSelectedRule)
            return;

        if (!$lastSelectedRule->can_stack) {
            // If the newly selected one is NOT stackable, clear others and keep only this one
            $this->selected_promo_ids = [$lastSelectedId];
        } else {
            // If the newly selected one IS stackable, remove any existing non-stackable ones
            $rules = PricingRule::whereIn('id', $this->selected_promo_ids)->get();
            $this->selected_promo_ids = $rules->filter(fn($r) => $r->can_stack)->pluck('id')->toArray();
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

        $units = Unit::whereIn('id', $this->selected_unit_ids)->get();

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
            $rules = PricingRule::whereIn('id', $this->selected_promo_ids)->where('is_active', true)->get();
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
            'no_wa' => $this->no_wa,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $finalWaktuSelesai,
            'subtotal_harga' => $this->subtotal,
            'potongan_diskon' => $this->potongan_diskon,
            'applied_promo_name' => $this->applied_promo_label ?: null,
            'hari_bonus' => $this->hari_bonus,
            'jam_bonus' => $this->jam_bonus,
            'kode_unik_pembayaran' => $this->kode_unik,
            'grand_total' => $this->grand_total,
            'status' => 'pending',
            'affiliate_code' => $this->referral_code ?: null,
            'affiliator_id' => $this->referral_code ? (\App\Models\AffiliatorProfile::where('referral_code', strtoupper($this->referral_code))->first()->user_id ?? null) : null,
        ]);

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
            $this->nikFoundMessage = 'Data ditemukan! Form otomatis terisi.';
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
        return view('livewire.front.booking-form')->layout('layouts.app');
    }
}
