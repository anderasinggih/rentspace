<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use Livewire\Component;

class UnitManager extends Component
{
    public $unit_id, $seri, $imei, $memori, $warna, $kondisi;
    public $harga_per_jam = 0;
    public $harga_per_hari = 0;
    public $is_active = true;
    public $isEditing = false;
    public $showModal = false;

    public function create()
    {
        $this->reset(['unit_id', 'seri', 'imei', 'memori', 'warna', 'kondisi', 'harga_per_jam', 'harga_per_hari', 'isEditing']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unit_id = $unit->id;
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
        $this->validate([
            'seri' => 'required|string',
            'imei' => 'required|string|unique:units,imei,' . $this->unit_id,
            'memori' => 'required|string',
            'warna' => 'required|string',
            'harga_per_jam' => 'required|numeric',
            'harga_per_hari' => 'required|numeric',
        ]);

        Unit::updateOrCreate(
            ['id' => $this->unit_id],
            [
                'seri' => $this->seri,
                'imei' => $this->imei,
                'memori' => $this->memori,
                'warna' => $this->warna,
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

    public function render()
    {
        return view('livewire.admin.unit-manager', [
            'units' => Unit::latest()->get()
        ])->layout('layouts.app');
    }
}
