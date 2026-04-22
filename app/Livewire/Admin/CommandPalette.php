<?php

namespace App\Livewire\Admin;

use App\Models\Rental;
use App\Models\Unit;
use Livewire\Component;
use Illuminate\Support\Collection;

class CommandPalette extends Component
{
    public $search = '';
    public $isOpen = false;
    public $selectedIndex = 0;

    protected $listeners = ['toggleCommandPalette' => 'toggle'];

    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
        $this->search = '';
        $this->selectedIndex = 0;
    }

    public function selectResult($url, $action = null)
    {
        $this->isOpen = false;
        
        if ($action === 'logout') {
            return $this->logout();
        }

        return $this->redirect($url, navigate: true);
    }

    public function logout()
    {
        \Illuminate\Support\Facades\Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function render()
    {
        $results = collect();

        if (strlen($this->search) >= 2) {
            // 1. Navigation Shortcuts
            $pages = [
                ['title' => 'Dashboard Overview', 'url' => route('admin.dashboard'), 'type' => 'Page', 'icon' => 'layout-dashboard'],
                ['title' => 'Monitoring Timeline', 'url' => route('admin.monitoring'), 'type' => 'Page', 'icon' => 'calendar'],
                ['title' => 'Manajemen Unit & Kategori', 'url' => route('admin.units'), 'type' => 'Page', 'icon' => 'smartphone'],
                ['title' => 'Log Transaksi Peminjaman', 'url' => route('admin.transactions'), 'type' => 'Page', 'icon' => 'receipt'],
                ['title' => 'Customer Insights (CRM)', 'url' => route('admin.customers'), 'type' => 'Page', 'icon' => 'users'],
                ['title' => 'Manajemen Affiliate', 'url' => route('admin.affiliate'), 'type' => 'Page', 'icon' => 'share-2'],
                ['title' => 'Pengaturan Sistem', 'url' => route('admin.settings'), 'type' => 'Page', 'icon' => 'settings'],
                ['title' => 'Keluar / Logout', 'url' => '#', 'type' => 'Action', 'icon' => 'log-out', 'action' => 'logout'],
                ['title' => 'Hapus Cache Sistem', 'url' => url('/clear-cache'), 'type' => 'Action', 'icon' => 'zap'],
            ];

            foreach ($pages as $page) {
                if (str_contains(strtolower($page['title']), strtolower($this->search))) {
                    $results->push((object)$page);
                }
            }

            // 2. Bookings (Rentals)
            $rentals = Rental::where('booking_code', 'like', '%' . $this->search . '%')
                ->orWhere('nama', 'like', '%' . $this->search . '%')
                ->latest()
                ->limit(5)
                ->get();

            foreach ($rentals as $rental) {
                $results->push((object)[
                    'title' => '#' . $rental->booking_code . ' - ' . $rental->nama,
                    'url' => route('admin.transactions', ['search' => $rental->booking_code]),
                    'type' => 'Pemesanan',
                    'icon' => 'package'
                ]);
            }

            // 3. Units
            $units = Unit::where('seri', 'like', '%' . $this->search . '%')
                ->orWhere('imei', 'like', '%' . $this->search . '%')
                ->orWhere('warna', 'like', '%' . $this->search . '%')
                ->limit(5)
                ->get();

            foreach ($units as $unit) {
                $results->push((object)[
                    'title' => 'Unit: ' . $unit->seri . ($unit->warna ? ' (' . $unit->warna . ')' : ''),
                    'url' => route('admin.units', ['search' => $unit->seri]),
                    'type' => 'Unit',
                    'icon' => 'smartphone'
                ]);
            }

            // 4. Customers (CRM)
            $customers = Rental::where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('nik', 'like', '%' . $this->search . '%')
                ->selectRaw('nik, nama')
                ->groupBy('nik', 'nama')
                ->limit(5)
                ->get();

            foreach ($customers as $customer) {
                $results->push((object)[
                    'title' => 'Pelanggan: ' . $customer->nama,
                    'url' => route('admin.customers', ['search' => $customer->nik]),
                    'type' => 'Pelanggan',
                    'icon' => 'user'
                ]);
            }
        }

        return view('livewire.admin.command-palette', [
            'results' => $results
        ]);
    }
}
