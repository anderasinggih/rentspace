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
        return \App\Helpers\CustomerHelper::getTier($ltv);
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
