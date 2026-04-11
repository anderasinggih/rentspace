<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use Livewire\Component;

class UnitManager extends Component
{
    public $unit_id, $seri, $imei, $memori, $warna, $kondisi;
    public $kategori = 'iphone';
    public $harga_per_jam = 0;
    public $harga_per_hari = 0;
    public $is_active = true;
    public $isEditing = false;
    public $showModal = false;

    public function create()
    {
        $this->reset(['unit_id', 'seri', 'imei', 'memori', 'warna', 'kondisi', 'harga_per_jam', 'harga_per_hari', 'isEditing']);
        $this->kategori = 'iphone';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unit_id = $unit->id;
        $this->kategori = $unit->kategori ?? 'iphone';
        $this->seri = $unit->seri;
        $this->imei = $unit->imei;
        $this->memori = $unit->memori;
        $this->warna = $unit->warna;
        $this->kondisi = $unit->kondisi;
        $this->harga_per_jam = $unit->harga_per_jam;
        $this->harga_per_hari = $unit->harga_per_hari;
        $this->is_active = $unit->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'kategori' => 'required|string',
            'seri' => 'required|string',
            'harga_per_jam' => 'required|numeric',
            'harga_per_hari' => 'required|numeric',
        ];

        if ($this->kategori === 'iphone') {
            $rules['imei'] = 'required|string|unique:units,imei,' . $this->unit_id;
            $rules['memori'] = 'required|string';
            $rules['warna'] = 'required|string';
        } else {
            $rules['imei'] = 'nullable|string|unique:units,imei,' . $this->unit_id;
            $rules['memori'] = 'nullable|string';
            $rules['warna'] = 'nullable|string';
        }

        $this->validate($rules);

        Unit::updateOrCreate(
            ['id' => $this->unit_id],
            [
                'kategori' => $this->kategori,
                'seri' => $this->seri,
                'imei' => $this->kategori === 'iphone' ? $this->imei : null,
                'memori' => $this->kategori === 'iphone' ? $this->memori : null,
                'warna' => $this->kategori === 'iphone' ? $this->warna : null,
                'kondisi' => $this->kondisi,
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
        \App\Models\Unit::withTrashed()->find($id)->restore();
    }

    public function render()
    {
        $query = \App\Models\Unit::withTrashed()->orderBy('deleted_at', 'asc')->orderBy('is_active', 'desc');
        
        return view('livewire.admin.unit-manager', [
            'units' => $query->get()
        ])->layout('layouts.admin');
    }
}
