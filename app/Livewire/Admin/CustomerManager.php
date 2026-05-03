<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Manajemen Pelanggan - Admin')]
class CustomerManager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $selectedNik = null;
    public $vipThreshold = 5; // 5+ orders = VIP

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectCustomer($nik)
    {
        $this->selectedNik = $nik;
    }

    public function closeDetail()
    {
        $this->selectedNik = null;
    }

    public function getTier($ltv)
    {
        if ($ltv >= 6000000) return (object)['label' => 'LEGEND', 'color' => 'bg-primary text-primary-foreground shadow-sm'];
        if ($ltv >= 3000000) return (object)['label' => 'DIAMOND', 'color' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'];
        if ($ltv >= 1000000) return (object)['label' => 'PLATINUM', 'color' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'];
        if ($ltv >= 500000) return (object)['label' => 'GOLD', 'color' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'];
        if ($ltv >= 100000) return (object)['label' => 'SILVER', 'color' => 'border-border bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'];
        
        return (object)['label' => 'BRONZE', 'color' => 'border-transparent bg-secondary text-secondary-foreground'];
    }

    public function render()
    {
        // Query to get unique customers based on NIK
        $customersQuery = Rental::selectRaw('nik, nama, no_wa, COUNT(id) as total_orders, SUM(grand_total) as ltv, MAX(created_at) as last_order')
            ->where(function($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('nik', 'like', '%' . $this->search . '%')
                  ->orWhere('no_wa', 'like', '%' . $this->search . '%');
            })
            ->groupBy('nik', 'nama', 'no_wa')
            ->orderByDesc('ltv');

        $customers = $customersQuery->paginate($this->perPage);

        $customerDetails = null;
        if ($this->selectedNik) {
            $customerDetails = Rental::with('units')
                ->where('nik', $this->selectedNik)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('livewire.admin.customer-manager', [
            'customers' => $customers,
            'customerDetails' => $customerDetails
        ])->layout('layouts.admin');
    }
}
