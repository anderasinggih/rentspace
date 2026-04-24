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
                $q->whereHas('user', function($qu) {
                    $qu->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('action', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.staff-logs', [
            'logs' => $logs
        ])->layout('layouts.admin');
    }
}
