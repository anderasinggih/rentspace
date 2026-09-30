<?php

namespace App\Livewire\Admin;

use App\Models\StaffLog;
use Livewire\Component;
use Livewire\WithPagination;

class StaffLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    public $category = 'all'; // all, transaksi, unit, promo, system
    public $selectedRole = '';
    public $selectedUser = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $selectedLogId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => 'all'],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingCategory() { $this->resetPage(); }
    public function updatingSelectedRole() { $this->resetPage(); }
    public function updatingSelectedUser() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    public function setCategory($cat)
    {
        $this->category = $cat;
        $this->resetPage();
    }

    public function openDetail($id)
    {
        $this->selectedLogId = $id;
    }

    public function closeDetail()
    {
        $this->selectedLogId = null;
    }

    public function resetFilters()
    {
        $this->reset(['search', 'category', 'selectedRole', 'selectedUser', 'dateFrom', 'dateTo']);
    }

    public function mount()
    {
        if (!in_array(auth()->user()->role ?? '', ['admin', 'staff'])) {
            abort(403);
        }
    }

    public function render()
    {
        $categoryMap = [
            'transaksi' => ['mark_as_paid', 'handover_unit', 'cancel_transaction', 'complete_rental', 'edit_transaction', 'extend_rental', 'denda_paid'],
            'unit' => ['add_unit', 'update_unit', 'delete_unit', 'restore_unit', 'force_delete_unit', 'manage_category', 'manage_unit'],
            'promo' => ['add_promo', 'update_promo', 'delete_promo', 'restore_promo', 'create_pricing_rule'],
            'system' => ['update_setting', 'whatsapp_broadcast', 'login', 'logout', 'payout_affiliate']
        ];

        $logs = StaffLog::with('user')
            ->when($this->search, function($q) {
                $q->where(function($qq) {
                    $qq->whereHas('user', function($qu) {
                        $qu->where('name', 'like', '%' . $this->search . '%')
                           ->orWhere('email', 'like', '%' . $this->search . '%');
                    })->orWhere('action', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('ip_address', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->category !== 'all', function($q) use ($categoryMap) {
                if (isset($categoryMap[$this->category])) {
                    $q->whereIn('action', $categoryMap[$this->category]);
                }
            })
            ->when($this->selectedRole, function($q) {
                $q->whereHas('user', function($qu) {
                    $qu->where('role', $this->selectedRole);
                });
            })
            ->when($this->selectedUser, function($q) {
                $q->where('user_id', $this->selectedUser);
            })
            ->when($this->dateFrom, function($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $counts = [
            'all' => StaffLog::count(),
            'transaksi' => StaffLog::whereIn('action', $categoryMap['transaksi'])->count(),
            'unit' => StaffLog::whereIn('action', $categoryMap['unit'])->count(),
            'promo' => StaffLog::whereIn('action', $categoryMap['promo'])->count(),
            'system' => StaffLog::whereIn('action', $categoryMap['system'])->count(),
        ];

        $selectedLog = $this->selectedLogId ? StaffLog::with('user')->find($this->selectedLogId) : null;

        $users = \App\Models\User::whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.staff-logs', [
            'logs' => $logs,
            'users' => $users,
            'counts' => $counts,
            'selectedLog' => $selectedLog
        ])->layout('layouts.admin');
    }
}
