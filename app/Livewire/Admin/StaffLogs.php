<?php

namespace App\Livewire\Admin;

use App\Models\StaffLog;
use Livewire\Component;
use Livewire\WithPagination;

class StaffLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 50;
    public $selectedRole = '';
    public $selectedUser = '';
    public $dateFrom = '';
    public $dateTo = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingSelectedRole() { $this->resetPage(); }
    public function updatingSelectedUser() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'selectedRole', 'selectedUser', 'dateFrom', 'dateTo']);
    }

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function render()
    {
        $logs = StaffLog::with('user')
            ->when($this->search, function($q) {
                $q->where(function($qq) {
                    $qq->whereHas('user', function($qu) {
                        $qu->where('name', 'like', '%' . $this->search . '%');
                    })->orWhere('action', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
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

        $users = \App\Models\User::whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.staff-logs', [
            'logs' => $logs,
            'users' => $users
        ])->layout('layouts.admin');
    }
}
