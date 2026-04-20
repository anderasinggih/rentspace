<?php

namespace App\Livewire\Admin;

use App\Models\PricingRule;
use Livewire\Component;


class PricingRules extends Component
{
    public $rule_id, $nama_promo, $kode_promo, $affiliate_code, $tipe = 'diskon_persen', $value, $syarat_minimal_durasi, $syarat_tipe_durasi = 'jam';
    public $start_date, $end_date;
    public $is_active = true, $is_hidden = false, $can_stack = false;
    public $is_affiliate_only = false, $requires_referral = false;
    public $isEditing = false;
    public $showModal = false;

    public function create()
    {
        $this->reset(['rule_id', 'nama_promo', 'kode_promo', 'affiliate_code', 'value', 'syarat_minimal_durasi', 'start_date', 'end_date', 'isEditing', 'is_hidden', 'can_stack', 'is_affiliate_only', 'requires_referral']);
        $this->tipe = 'diskon_persen';
        $this->syarat_tipe_durasi = 'jam';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $rule = PricingRule::withTrashed()->findOrFail($id);
        $this->rule_id = $rule->id;
        $this->nama_promo = $rule->nama_promo;
        $this->kode_promo = $rule->kode_promo;
        $this->affiliate_code = $rule->affiliate_code;
        $this->tipe = $rule->tipe;
        $this->value = $rule->value;
        $this->syarat_minimal_durasi = $rule->syarat_minimal_durasi;
        $this->syarat_tipe_durasi = $rule->syarat_tipe_durasi;
        $this->start_date = $rule->start_date;
        $this->end_date = $rule->end_date;
        $this->is_active = $rule->is_active;
        $this->is_hidden = (bool)$rule->is_hidden;
        $this->can_stack = (bool)$rule->can_stack;
        $this->is_affiliate_only = (bool)$rule->is_affiliate_only;
        $this->requires_referral = (bool)$rule->requires_referral;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function duplicate($id)
    {
        $rule = PricingRule::withTrashed()->findOrFail($id);
        $this->reset(['rule_id', 'isEditing']);
        $this->nama_promo = $rule->nama_promo . ' (Copy)';
        $this->kode_promo = $rule->kode_promo ? $rule->kode_promo . '-COPY' : null;
        $this->affiliate_code = $rule->affiliate_code;
        $this->tipe = $rule->tipe;
        $this->value = $rule->value;
        $this->syarat_minimal_durasi = $rule->syarat_minimal_durasi;
        $this->syarat_tipe_durasi = $rule->syarat_tipe_durasi;
        $this->start_date = $rule->start_date;
        $this->end_date = $rule->end_date;
        $this->is_active = true;
        $this->is_hidden = (bool)$rule->is_hidden;
        $this->can_stack = (bool)$rule->can_stack;
        $this->is_affiliate_only = (bool)$rule->is_affiliate_only;
        $this->requires_referral = (bool)$rule->requires_referral;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'nama_promo' => 'required|string',
            'kode_promo' => 'nullable|string|unique:pricing_rules,kode_promo,'.$this->rule_id,
            'tipe' => 'required|string|in:diskon_persen,hari_gratis,fix_price,diskon_nominal,jam_gratis,cashback',
            'value' => 'required|numeric',
            'syarat_minimal_durasi' => 'nullable|numeric',
            'syarat_tipe_durasi' => 'required|string|in:jam,hari',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        PricingRule::updateOrCreate(
            ['id' => $this->rule_id],
            [
                'nama_promo' => $this->nama_promo,
                'kode_promo' => $this->kode_promo ?: null,
                'tipe' => $this->tipe,
                'value' => $this->value,
                'syarat_minimal_durasi' => $this->syarat_minimal_durasi,
                'syarat_tipe_durasi' => $this->syarat_tipe_durasi,
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'is_active' => $this->is_active,
                'is_hidden' => $this->is_hidden,
                'can_stack' => $this->can_stack,
                'is_affiliate_only' => $this->is_affiliate_only,
                'requires_referral' => $this->requires_referral,
                'deleted_at' => null, // Restore if it was soft deleted
            ]
        );

        $this->showModal = false;
    }

    public function restore($id)
    {
        PricingRule::withTrashed()->findOrFail($id)->restore();
    }

    public function delete($id)
    {
        $rule = PricingRule::withTrashed()->findOrFail($id);
        if ($rule->trashed()) {
            $rule->forceDelete();
        } else {
            $rule->delete();
        }
    }

    public function render()
    {
        return view('livewire.admin.pricing-rules', [
            'rules' => PricingRule::withTrashed()->latest()->get()
        ])->layout('layouts.admin');
    }
}
