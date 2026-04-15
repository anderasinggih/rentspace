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
    public $unit_id;
    public $available_units = [];
    public $subtotal = 0;
    public $potongan_diskon = 0;
    public $grand_total = 0;
    public $kode_unik = 0;
    public $agree = false;

    // Promo selection
    public $selected_promo_id = null;
    public $available_promos = [];
    public $applied_promo_label = '';
    public $hari_bonus = 0; // extra days added by hari_gratis promo
    public $jam_bonus = 0;  // extra hours added by jam_gratis promo

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai', 'unit_id'])) {
            $this->checkAvailability();
            $this->loadAvailablePromos();
        }
        if ($propertyName === 'selected_promo_id') {
            $this->calculatePrice();
        }
        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai'])) {
            $this->calculatePrice();
        }
    }

    public function checkAvailability()
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai) return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);

        if ($end->lte($start)) {
            $this->addError('waktu_selesai', 'Waktu selesai harus setelah waktu mulai');
            $this->available_units = [];
            return;
        }

        $this->available_units = Unit::where('is_active', true)
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
            
        if ($this->unit_id && !$this->available_units->contains('id', $this->unit_id)) {
            $this->unit_id = null;
        }

        $this->calculatePrice();
    }

    public function loadAvailablePromos()
    {
        if (!$this->waktu_mulai || !$this->waktu_selesai || !$this->unit_id) {
            $this->available_promos = [];
            return;
        }

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);
        $diffInHours = $start->diffInHours($end);
        $days = floor($diffInHours / 24);

        $now = Carbon::now();
        $rules = PricingRule::where('is_active', true)
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now->format('Y-m-d'));
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now->format('Y-m-d'));
            })
            ->get();

        $this->available_promos = $rules->map(function($rule) use ($days, $diffInHours) {
            $durasiTerkonversi = $rule->syarat_tipe_durasi === 'hari' ? $days : $diffInHours;
            $is_eligible = !$rule->syarat_minimal_durasi || $durasiTerkonversi >= $rule->syarat_minimal_durasi;
            
            return array_merge($rule->toArray(), [
                'is_eligible' => $is_eligible
            ]);
        })->values()->toArray();
    }

    public function calculatePrice()
    {
        if (!$this->unit_id || !$this->waktu_mulai || !$this->waktu_selesai) {
            $this->subtotal = 0;
            $this->grand_total = 0;
            $this->hari_bonus = 0;
            $this->jam_bonus = 0;
            return;
        }

        $unit = Unit::find($this->unit_id);
        if (!$unit) return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);
        
        $diffInHours = $start->diffInHours($end);
        if ($diffInHours == 0) $diffInHours = 1;

        $days = floor($diffInHours / 24);
        $remainingHours = $diffInHours % 24;

        $this->subtotal = ($days * $unit->harga_per_hari) + ($remainingHours * $unit->harga_per_jam);

        $this->potongan_diskon = 0;
        $this->hari_bonus = 0;
        $this->jam_bonus = 0;
        $this->applied_promo_label = '';

        if ($this->selected_promo_id) {
            $rule = PricingRule::find($this->selected_promo_id);
            if ($rule && $rule->is_active) {
                $this->applied_promo_label = $rule->nama_promo;
                
                if ($rule->tipe === 'diskon_persen') {
                    $this->potongan_diskon = $this->subtotal * ($rule->value / 100);
                } elseif ($rule->tipe === 'hari_gratis') {
                    // Extend the stay by value days (no price discount — free days added to time)
                    $this->hari_bonus = (int) $rule->value;
                } elseif ($rule->tipe === 'jam_gratis') {
                    $this->jam_bonus = (int) $rule->value;
                } elseif ($rule->tipe === 'diskon_nominal') {
                    $this->potongan_diskon = $rule->value;
                } elseif ($rule->tipe === 'fix_price') {
                    $this->potongan_diskon = max(0, $this->subtotal - $rule->value);
                }
            }
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
            'unit_id' => 'required|exists:units,id',
            'agree' => 'accepted',
        ], [
            'nik.numeric' => 'NIK harus berupa angka.',
            'no_wa.numeric' => 'Nomor WhatsApp harus berupa angka.',
            'agree.accepted' => 'Anda wajib menyetujui syarat & ketentuan penyewaan sebelum melanjutkan.',
        ]);

        $this->checkAvailability();
        if (!$this->available_units->contains('id', $this->unit_id)) {
            $this->addError('unit_id', 'Unit ini tidak tersedia di slot waktu yang Anda pilih.');
            return;
        }

        // If hari_gratis promo applied, extend waktu_selesai
        $finalWaktuSelesai = $this->waktu_selesai;
        if ($this->hari_bonus > 0) {
            $finalWaktuSelesai = Carbon::parse($this->waktu_selesai)->addDays($this->hari_bonus)->format('Y-m-d\TH:i');
        }
        if ($this->jam_bonus > 0) {
            $finalWaktuSelesai = Carbon::parse($finalWaktuSelesai)->addHours($this->jam_bonus)->format('Y-m-d\TH:i');
        }

        $rental = Rental::create([
            'unit_id' => $this->unit_id,
            'nik' => $this->nik,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
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
            'status' => 'pending'
        ]);

        return redirect()->route('public.payment', $rental->id);
    }

    public function render()
    {
        return view('livewire.front.booking-form')->layout('layouts.app');
    }
}
