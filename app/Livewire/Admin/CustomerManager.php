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
    public $selectedNoWa = null;
    public $vipThreshold = 5; // 5+ orders = VIP

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectCustomer($no_wa)
    {
        $this->selectedNoWa = $no_wa;
    }

    public function closeDetail()
    {
        $this->selectedNoWa = null;
    }

    public function getTier($ltv)
    {
        return \App\Helpers\CustomerHelper::getTier($ltv);
    }

    public function render()
    {
        // Query to get unique customers based on No. WA
        $customersQuery = Rental::selectRaw('no_wa, MAX(nama) as nama, COUNT(id) as total_orders, SUM(grand_total) as ltv, MAX(created_at) as last_order')
            ->where(function($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('no_wa', 'like', '%' . $this->search . '%');
            })
            ->groupBy('no_wa')
            ->orderByDesc('ltv');

        $customers = $customersQuery->paginate($this->perPage);

        $customerDetails = null;
        $customerInsights = [];
        if ($this->selectedNoWa) {
            $customerDetails = Rental::with('units.category')
                ->where('no_wa', $this->selectedNoWa)
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
                'no_wa' => $customerDetails->first()->no_wa ?? '-',
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
