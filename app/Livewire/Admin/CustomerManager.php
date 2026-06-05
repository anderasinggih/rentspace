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
        $customersQuery = Rental::selectRaw('nik, MAX(nama) as nama, MAX(no_wa) as no_wa, COUNT(id) as total_orders, SUM(grand_total) as ltv, MAX(created_at) as last_order')
            ->where(function($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('nik', 'like', '%' . $this->search . '%')
                  ->orWhere('no_wa', 'like', '%' . $this->search . '%');
            })
            ->groupBy('nik')
            ->orderByDesc('ltv');

        $customers = $customersQuery->paginate($this->perPage);

        $customerDetails = null;
        $customerInsights = [];
        if ($this->selectedNik) {
            $customerDetails = Rental::with('units.category')
                ->where('nik', $this->selectedNik)
                ->orderByDesc('created_at')
                ->get();

            // Calculate Behavioral Insights
            $units = [];
            foreach($customerDetails as $r) {
                foreach($r->units as $u) {
                    $units[$u->seri] = ($units[$u->seri] ?? 0) + 1;
                }
            }
            arsort($units);
            
            $customerInsights = [
                'fav_unit' => array_key_first($units) ?? '-',
                'member_since' => $customerDetails->last()->created_at,
                'avg_transaction' => $customerDetails->avg('grand_total'),
                'total_rentals' => $customerDetails->count(),
                'last_rental' => $customerDetails->first()->created_at,
                'address' => $customerDetails->first()->alamat ?? '-',
                'sosmed' => $customerDetails->first()->sosial_media ?? '-',
                'email' => $customerDetails->first()->email ?? '-',
                'nik' => $customerDetails->first()->nik ?? '-',
                'nama' => $customerDetails->first()->nama ?? '-',
            ];
        }

        return view('livewire.admin.customer-manager', [
            'customers' => $customers,
            'customerDetails' => $customerDetails,
            'customerInsights' => $customerInsights
        ])->layout('layouts.admin');
    }
}
