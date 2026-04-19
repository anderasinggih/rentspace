<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use Livewire\Component;

class UnitManager extends Component
{
    public $unit_id, $seri, $imei, $memori, $warna, $kondisi;
    public $category_id, $harga_per_jam, $harga_per_hari;
    public $specs = []; // Dynamic specifications
    public $is_active = true;
    public $isEditing = false;
    public $showModal = false;

    // Search & Filter
    public $search = '';
    public $filterKategori = '';
    public $filterStatus = '';

    public function create()
    {
        $this->reset(['unit_id', 'seri', 'imei', 'memori', 'warna', 'kondisi', 'harga_per_jam', 'harga_per_hari', 'specs', 'isEditing']);
        $this->category_id = '';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unit_id = $unit->id;
        $this->category_id = $unit->category_id;
        $this->seri = $unit->seri;
        $this->imei = $unit->imei;
        $this->memori = $unit->memori;
        $this->warna = $unit->warna;
        $this->kondisi = $unit->kondisi;
        $this->specs = $unit->specs ?? [];
        $this->harga_per_jam = $unit->harga_per_jam;
        $this->harga_per_hari = $unit->harga_per_hari;
        $this->is_active = $unit->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $selectedCat = \App\Models\Category::find($this->category_id);
        $isIphone = $selectedCat && str_contains(strtolower($selectedCat->slug), 'iphone');

        $rules = [
            'category_id' => 'required',
            'seri' => 'required|string',
            'harga_per_jam' => 'required|numeric',
            'harga_per_hari' => 'required|numeric',
            'specs.*' => 'nullable|string',
        ];

        if ($isIphone) {
            $rules['imei'] = 'required|string|unique:units,imei,' . $this->unit_id;
            $rules['memori'] = 'required|string';
            $rules['warna'] = 'required|string';
        } else {
            $rules['imei'] = 'nullable|string|unique:units,imei,' . $this->unit_id;
        }

        $this->validate($rules);

        Unit::updateOrCreate(
            ['id' => $this->unit_id],
            [
                'category_id' => $this->category_id,
                'seri' => $this->seri,
                'imei' => $isIphone ? $this->imei : null,
                'memori' => $isIphone ? $this->memori : null,
                'warna' => $isIphone ? $this->warna : null,
                'kondisi' => $this->kondition ?? $this->kondisi,
                'specs' => $this->specs,
                'harga_per_jam' => $this->harga_per_jam,
                'harga_per_hari' => $this->harga_per_hari,
                'is_active' => $this->is_active,
            ]
        );

        $this->showModal = false;
    }

    public function delete($id)
    {
        Unit::findOrFail($id)->delete();
    }

    public function restoreUnit($id)
    {
        Unit::withTrashed()->find($id)->restore();
    }

    public function render()
    {
        $query = Unit::withTrashed()
            ->with('category')
            ->when($this->search, fn($q) => $q->where('seri', 'like', '%' . $this->search . '%')
                ->orWhere('imei', 'like', '%' . $this->search . '%')
                ->orWhere('warna', 'like', '%' . $this->search . '%'))
            ->when($this->filterKategori, fn($q) => $q->where('category_id', $this->filterKategori))
            ->when($this->filterStatus !== '', function ($q) {
                if ($this->filterStatus === 'active')
                    return $q->whereNull('deleted_at')->where('is_active', true);
                if ($this->filterStatus === 'inactive')
                    return $q->whereNull('deleted_at')->where('is_active', false);
                if ($this->filterStatus === 'deleted')
                    return $q->whereNotNull('deleted_at');
            })
            ->orderBy('deleted_at', 'asc')
            ->orderBy('is_active', 'desc');

        return view('livewire.admin.unit-manager', [
            'units' => $query->get(),
            'categories' => \App\Models\Category::orderBy('name')->get()
        ])->layout('layouts.admin');
    }
}