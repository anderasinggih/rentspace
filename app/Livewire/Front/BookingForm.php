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
    
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['waktu_mulai', 'waktu_selesai'])) {
            $this->checkAvailability();
        }
        $this->calculatePrice();
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
                $query->whereIn('status', ['pending', 'paid'])  // 'completed' = sudah selesai, unit bebas
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

    public function calculatePrice()
    {
        if (!$this->unit_id || !$this->waktu_mulai || !$this->waktu_selesai) {
            $this->subtotal = 0;
            $this->grand_total = 0;
            return;
        }

        $unit = Unit::find($this->unit_id);
        if (!$unit) return;

        $start = Carbon::parse($this->waktu_mulai);
        $end = Carbon::parse($this->waktu_selesai);
        
        $diffInHours = $start->diffInHours($end);
        if ($diffInHours == 0) $diffInHours = 1; // minimum 1 hr

        $days = floor($diffInHours / 24);
        $remainingHours = $diffInHours % 24;

        $this->subtotal = ($days * $unit->harga_per_hari) + ($remainingHours * $unit->harga_per_jam);

        $this->potongan_diskon = 0;
        
        // Cek rule diskon yang aktif dan dalam rentang tanggal
        $now = Carbon::now();
        $rules = PricingRule::where('is_active', true)
            ->where(function($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now->format('Y-m-d'));
            })
            ->where(function($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now->format('Y-m-d'));
            })
            ->get();
        foreach ($rules as $rule) {
            // Cek durasi minimum terpenuhi atau tidak
            $durasiTerkonversi = $rule->syarat_tipe_durasi === 'hari' ? $days : $diffInHours;
            if (!$rule->syarat_minimal_durasi || $durasiTerkonversi >= $rule->syarat_minimal_durasi) {
                
                if ($rule->tipe === 'diskon_persen') {
                    $diskon = $this->subtotal * ($rule->value / 100);
                    $this->potongan_diskon += $diskon;
                } elseif ($rule->tipe === 'hari_gratis') {
                    // Beri potongan seharga per hari
                    $this->potongan_diskon += $unit->harga_per_hari * $rule->value;
                } elseif ($rule->tipe === 'fix_price') {
                    // override the entire price instead? Or maybe standard discount nominal
                    $this->potongan_diskon += $rule->value;
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
            'nik' => 'required',
            'nama' => 'required',
            'no_wa' => 'required',
            'alamat' => 'required',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after:waktu_mulai',
            'unit_id' => 'required|exists:units,id',
            'agree' => 'accepted',
        ], [
            'agree.accepted' => 'Anda wajib menyetujui syarat & ketentuan penyewaan sebelum melanjutkan.',
        ]);

        $this->checkAvailability();
        if (!$this->available_units->contains('id', $this->unit_id)) {
            $this->addError('unit_id', 'Unit ini tidak tersedia di slot waktu yang Anda pilih.');
            return;
        }

        $rental = Rental::create([
            'unit_id' => $this->unit_id,
            'nik' => $this->nik,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'no_wa' => $this->no_wa,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            'subtotal_harga' => $this->subtotal,
            'potongan_diskon' => $this->potongan_diskon,
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
