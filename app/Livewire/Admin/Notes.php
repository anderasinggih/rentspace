<?php

namespace App\Livewire\Admin;

use App\Models\PinnedReport;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;

class Notes extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'all'; // all, pinned, unpinned
    public $reportGroupId = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->reportGroupId = Setting::sanitizeJid(Setting::getVal('admin_report_group_id', ''));
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function togglePin($id)
    {
        $report = PinnedReport::find($id);
        if ($report) {
            $report->is_pinned = !$report->is_pinned;
            if ($report->is_pinned && !$report->pinned_at) {
                $report->pinned_at = now();
            }
            $report->save();
        }
    }

    public function deleteNote($id)
    {
        PinnedReport::where('id', $id)->delete();
    }

    public function render()
    {
        $query = PinnedReport::query();

        if ($this->filter === 'pinned') {
            $query->where('is_pinned', true);
        } elseif ($this->filter === 'unpinned') {
            $query->where('is_pinned', false);
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('message_text', 'like', "%{$s}%")
                  ->orWhere('sender_name', 'like', "%{$s}%")
                  ->orWhere('sender_phone', 'like', "%{$s}%");
            });
        }

        $notes = $query->orderByDesc('is_pinned')
                      ->orderByDesc('pinned_at')
                      ->orderByDesc('created_at')
                      ->paginate(15);

        $pinnedCount = PinnedReport::where('is_pinned', true)->count();
        $totalCount = PinnedReport::count();

        return view('livewire.admin.notes', [
            'notes' => $notes,
            'pinnedCount' => $pinnedCount,
            'totalCount' => $totalCount,
        ])->layout('layouts.admin', [
            'title' => 'Notes Grup Reporting',
        ]);
    }
}
