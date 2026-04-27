<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use App\Models\Rental;
use Livewire\Component;
use Livewire\Attributes\Title;
use Carbon\Carbon;

#[Title('Monitoring Timeline - Admin')]
class Monitoring extends Component
{
    use \App\Traits\LogsStaffActivity;

    public $timeframe = '14'; 
    public $filterCategoryId = '';
    public $search = '';
    public $customStartDate;
    public $customEndDate;
    public $selectedRentalId = null;

    // Completion/Denda Properties
    public $completingTrxId = null;
    public $dendaAmount = 0;
    public $dendaKerusakanAmount = 0;
    public $catatanKerusakan = '';
    public $dendaMethod = 'cash';
    public $lateDurationText = '';
    public $isOverdue = false;

    public function mount()
    {
        $this->customStartDate = Carbon::today()->format('Y-m-d');
        $this->customEndDate = Carbon::today()->addDays(14)->format('Y-m-d');
    }

    public function nextPage()
    {
        $days = is_numeric($this->timeframe) ? (int)$this->timeframe : 30;
        $this->customStartDate = Carbon::parse($this->customStartDate)->addDays($days)->format('Y-m-d');
        $this->customEndDate = Carbon::parse($this->customEndDate)->addDays($days)->format('Y-m-d');
        $this->timeframe = 'custom';
    }

    public function previousPage()
    {
        $days = is_numeric($this->timeframe) ? (int)$this->timeframe : 30;
        $this->customStartDate = Carbon::parse($this->customStartDate)->subDays($days)->format('Y-m-d');
        $this->customEndDate = Carbon::parse($this->customEndDate)->subDays($days)->format('Y-m-d');
        $this->timeframe = 'custom';
    }

    public function selectRental($id)
    {
        $this->selectedRentalId = $id;
        $this->dispatch('open-rental-modal');
    }

    public function closeDetail()
    {
        $this->selectedRentalId = null;
    }

    public function getSelectedRentalProperty()
    {
        if (!$this->selectedRentalId) return null;
        return Rental::with(['units.locations' => function($q) {
            $q->latest()->limit(50);
        }])->find($this->selectedRentalId);
    }

    public function markAsPaid($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'pending') {
            $rental->update(['status' => 'paid']);
            $this->calculateAffiliateCommission($rental);
            
            $this->logActivity('mark_as_paid', $rental, "Memvalidasi pembayaran transaksi #{$rental->id} via Monitoring");
        }
    }

    public function cancel($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $rental = Rental::findOrFail($id);
        if (in_array($rental->status, ['pending', 'paid'])) {
            $rental->update(['status' => 'cancelled']);
            $this->logActivity('cancel_transaction', $rental, "Membatalkan transaksi #{$rental->id} via Monitoring");
        }
    }

    public function openDendaModal($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff']))
            return;
        $trx = Rental::findOrFail($id);
        $this->completingTrxId = $id;
        $this->dendaAmount = 0;
        $this->dendaKerusakanAmount = 0;
        $this->catatanKerusakan = '';
        $this->dendaMethod = 'cash';

        // Calculate late duration
        $end = \Carbon\Carbon::parse($trx->waktu_selesai);
        $this->isOverdue = $end->isPast();
        $diff = now()->diff($end);

        $parts = [];
        if ($diff->d > 0) $parts[] = $diff->d . 'd';
        if ($diff->h > 0) $parts[] = $diff->h . 'h';
        if ($diff->i > 0) $parts[] = $diff->i . 'm';
        $this->lateDurationText = !empty($parts) ? implode(' ', $parts) : '0m';
    }

    public function closeDendaModal()
    {
        $this->completingTrxId = null;
    }

    public function confirmDenda()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $this->validate(['dendaAmount' => 'required|numeric|min:0', 'dendaKerusakanAmount' => 'required|numeric|min:0', 'dendaMethod' => 'required|in:cash,qris']);

        if ($this->completingTrxId) {
            $rental = Rental::findOrFail($this->completingTrxId);
            if ($rental->status === 'renting') {
                $newGrandTotal = $rental->grand_total + (int)$this->dendaAmount + (int)$this->dendaKerusakanAmount;
                $rental->update([
                    'status' => 'completed',
                    'denda' => (int)$this->dendaAmount,
                    'denda_kerusakan' => (int)$this->dendaKerusakanAmount,
                    'catatan_kerusakan' => $this->catatanKerusakan,
                    'grand_total' => $newGrandTotal,
                    'denda_payment_method' => ($this->dendaAmount > 0 || $this->dendaKerusakanAmount > 0) ? $this->dendaMethod : null,
                    'completed_at' => now(),
                ]);
                $this->calculateAffiliateCommission($rental);
                $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa #{$rental->id} via Monitoring");
            }
        }
        $this->closeDendaModal();
    }

    public function finishWithoutDenda($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'renting') {
            $rental->update(['status' => 'completed', 'denda' => 0, 'denda_payment_method' => null, 'completed_at' => now()]);
            $this->calculateAffiliateCommission($rental);
            $this->logActivity('complete_rental', $rental, "Menyelesaikan sewa #{$rental->id} tanpa denda via Monitoring");
        }
    }

    public function handover($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $rental = Rental::findOrFail($id);
        if ($rental->status === 'paid') {
            $rental->update(['status' => 'renting', 'handed_over_at' => now()]);
            $this->logActivity('handover_unit', $rental, "Validasi ambil unit untuk transaksi #{$rental->id}");
            session()->flash('message', 'Unit berhasil divalidasi ambil. Status sekarang: Renting.');
        }
    }

    private function calculateAffiliateCommission($rental)
    {
        if ($rental->affiliator_id) {
            $exists = \App\Models\AffiliateCommission::where('rental_id', $rental->id)->exists();
            if ($exists) return;
            $profile = \App\Models\AffiliatorProfile::where('user_id', $rental->affiliator_id)->first();
            if ($profile && $profile->status === 'approved') {
                $amount = $rental->subtotal_harga * ($profile->commission_rate / 100);
                \App\Models\AffiliateCommission::create(['affiliator_id' => $rental->affiliator_id, 'rental_id' => $rental->id, 'amount' => $amount, 'status' => 'earned']);
                $totalEarned = \App\Models\AffiliateCommission::where('affiliator_id', $rental->affiliator_id)->sum('amount');
                $totalWithdrawn = \App\Models\AffiliatePayout::where('affiliator_id', $rental->affiliator_id)->sum('amount');
                $profile->balance = $totalEarned - $totalWithdrawn;
                $profile->save();
            }
        }
    }

    public function render()
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(13);

        if ($this->timeframe === '7') {
            $endDate = $startDate->copy()->addDays(6);
        } elseif ($this->timeframe === '14') {
            $endDate = $startDate->copy()->addDays(13);
        } elseif ($this->timeframe === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($this->timeframe === 'year') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now()->endOfYear();
        } elseif ($this->timeframe === 'all') {
            $firstRental = Rental::min('waktu_mulai');
            $startDate = $firstRental ? Carbon::parse($firstRental)->startOfDay() : Carbon::now()->subMonths(3);
            $endDate = Carbon::now()->addMonths(1)->endOfDay();
        } elseif ($this->timeframe === 'custom' && $this->customStartDate && $this->customEndDate) {
            $startDate = Carbon::parse($this->customStartDate)->startOfDay();
            $endDate = Carbon::parse($this->customEndDate)->endOfDay();
        }

        $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
        
        // Limit total days to prevent browser crash (e.g. 2 years max)
        if ($totalDays > 730) {
            $totalDays = 730;
            $endDate = $startDate->copy()->addDays($totalDays - 1);
        }

        $dates = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $dates[] = $startDate->copy()->addDays($i);
        }

        // 1. Fetch Timeline Units & Rentals
        $unitsQuery = Unit::query()->with(['category', 'rentals' => function ($q) use ($startDate, $endDate) {
            $q->whereIn('status', ['paid', 'pending', 'completed', 'renting'])
              ->where('waktu_mulai', '<=', $endDate)
              ->where('waktu_selesai', '>=', $startDate)
              ->when($this->search, function($q) {
                  $q->where(function($sq) {
                      $sq->where('nama', 'like', '%'.$this->search.'%')
                         ->orWhere('no_wa', 'like', '%'.$this->search.'%');
                  });
              });
        }]);

        if ($this->filterCategoryId) {
            $unitsQuery->where('category_id', $this->filterCategoryId);
        }

        if ($this->search) {
            $unitsQuery->where(function($q) {
                $q->where('seri', 'like', '%'.$this->search.'%')
                  ->orWhereHas('rentals', function($sq) {
                      $sq->where('nama', 'like', '%'.$this->search.'%')
                         ->orWhere('no_wa', 'like', '%'.$this->search.'%');
                  });
            });
        }

        $units = $unitsQuery->orderBy('category_id')->get();
        $categories = \App\Models\Category::orderBy('name')->get();

        // 3. Fetch Currently Rented Units (Active Now)
        // 3. Fetch Currently Rented Units (Status is 'renting' and started)
        $activeRentalsQuery = Rental::with(['units.category', 'units.locations' => function($q) use ($startDate, $endDate) {
            $q->latest()->limit(1);
        }])
            ->where('status', 'renting');
            
        if ($this->search) {
            $activeRentalsQuery->where(function($q) {
                $q->where('nama', 'like', '%'.$this->search.'%')
                  ->orWhere('no_wa', 'like', '%'.$this->search.'%')
                  ->orWhereHas('units', fn($sq) => $sq->where('seri', 'like', '%'.$this->search.'%'));
            });
        }
        
        $activeRentals = $activeRentalsQuery->orderBy('waktu_selesai', 'asc')->get();

        // 4. Fetch Upcoming & Ready to Collect (Status is 'paid' or 'pending')
        $upcomingRentalsQuery = Rental::with(['units.category'])
            ->whereIn('status', ['paid', 'pending']);
            // Note: we don't strictly filter by waktu_mulai > now anymore, 
            // since a PAID rental that is supposed to start might be waiting for pickup

        if ($this->search) {
            $upcomingRentalsQuery->where(function($q) {
                $q->where('nama', 'like', '%'.$this->search.'%')
                  ->orWhere('no_wa', 'like', '%'.$this->search.'%')
                  ->orWhereHas('units', fn($sq) => $sq->where('seri', 'like', '%'.$this->search.'%'));
            });
        }

        $upcomingRentals = $upcomingRentalsQuery->orderBy('waktu_mulai', 'asc')->get();

        // 5. Fetch Available Units (Not currently rented)
        $activeUnitIds = Rental::whereIn('status', ['paid', 'renting'])
            ->where('waktu_mulai', '<=', now())
            ->where('waktu_selesai', '>=', now())
            ->get()
            ->flatMap(fn($r) => $r->units->pluck('id'))
            ->unique();

        $availableUnitsQuery = Unit::with('category')
            ->whereNotIn('id', $activeUnitIds)
            ->when($this->filterCategoryId, fn($q) => $q->where('category_id', $this->filterCategoryId));

        if ($this->search) {
            $availableUnitsQuery->where('seri', 'like', '%'.$this->search.'%');
        }

        $availableUnits = $availableUnitsQuery->orderBy('category_id')->get();

        // 6. Stats for Summary Cards
        $endingSoonCount = Rental::whereIn('status', ['paid', 'renting'])
            ->where('waktu_selesai', '>', now())
            ->where('waktu_selesai', '<=', now()->addHours(6))
            ->count();
            
        $pendingCount = $upcomingRentals->where('status', 'pending')->count();

        return view('livewire.admin.monitoring', [
            'units' => $units,
            'categories' => $categories,
            'dates' => $dates,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDays' => $totalDays,
            'activeRentals' => $activeRentals,
            'upcomingRentals' => $upcomingRentals,
            'availableUnits' => $availableUnits,
            'endingSoonCount' => $endingSoonCount,
            'pendingCount' => $pendingCount
        ])->layout('layouts.admin');
    }
}
