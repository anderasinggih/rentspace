<?php

namespace App\Livewire\Admin;

use App\Models\PricingRule;
use Livewire\Component;

class PricingRules extends Component
{
    public $rule_id, $nama_promo, $tipe = 'diskon_persen', $value, $syarat_minimal_durasi, $syarat_tipe_durasi = 'jam';
    public $is_active = true;
    public $isEditing = false;
    public $showModal = false;

    public function create()
    {
        $this->reset(['rule_id', 'nama_promo', 'value', 'syarat_minimal_durasi', 'isEditing']);
        $this->tipe = 'diskon_persen';
        $this->syarat_tipe_durasi = 'jam';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $rule = PricingRule::findOrFail($id);
        $this->rule_id = $rule->id;
        $this->nama_promo = $rule->nama_promo;
        $this->tipe = $rule->tipe;
        $this->value = $rule->value;
        $this->syarat_minimal_durasi = $rule->syarat_minimal_durasi;
        $this->syarat_tipe_durasi = $rule->syarat_tipe_durasi;
        $this->is_active = $rule->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'nama_promo' => 'required|string',
            'tipe' => 'required|string|in:diskon_persen,hari_gratis,fix_price',
            'value' => 'required|numeric',
            'syarat_minimal_durasi' => 'nullable|numeric',
            'syarat_tipe_durasi' => 'required|string|in:jam,hari',
        ]);

        PricingRule::updateOrCreate(
            ['id' => $this->rule_id],
            [
                'nama_promo' => $this->nama_promo,
                'tipe' => $this->tipe,
                'value' => $this->value,
                'syarat_minimal_durasi' => $this->syarat_minimal_durasi,
                'syarat_tipe_durasi' => $this->syarat_tipe_durasi,
                'is_active' => $this->is_active,
            ]
        );

        $this->showModal = false;
    }

    public function delete($id)
    {
        PricingRule::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.admin.pricing-rules', [
            'rules' => PricingRule::latest()->get()
        ])->layout('layouts.admin');
    }
}
