<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Rental;

class RatingManager extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deleteRating($id)
    {
        if (auth()->user()->role !== 'admin') return;
        
        $rental = Rental::findOrFail($id);
        $rental->update([
            'rating' => null,
            'feedback' => null
        ]);
        
        session()->flash('rating_message', 'Data feedback berhasil dihapus.');
    }

    public function render()
    {
        $ratings = Rental::query()
            ->whereNotNull('rating')
            ->when($this->search, function ($query) {
                $query->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('feedback', 'like', '%' . $this->search . '%')
                    ->orWhere('booking_code', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.rating-manager', [
            'ratings' => $ratings
        ])->layout('layouts.admin');
    }
}
