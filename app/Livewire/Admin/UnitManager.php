<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use Livewire\Component;

use Livewire\WithPagination;

class UnitManager extends Component
{
    use WithPagination, \App\Traits\LogsStaffActivity;
    public $perPage = 20;
    public $unit_id, $seri, $imei, $memori, $warna, $kondisi, $is_active;
    public $category_id, $harga_per_jam, $harga_per_hari;
    public $specs = []; // Dynamic specifications
    public $isEditing = false;
    public $showModal = false;
    public $showQrModal = false;
    public $selectedUnitForQr = null;

    // Search & Filter
    public $search = '';
    public $filterKategori = '';
    public $filterStatus = '';
    public $activeTab = 'units'; // 'units' or 'categories'

    public $cat_id, $cat_name, $cat_slug, $cat_icon;
    public $cat_fields = [];
    public $showCatModal = false;
    public $isEditingCat = false;

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterKategori() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedActiveTab() { $this->resetPage(); }

    public function create()
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $this->reset(['unit_id', 'seri', 'imei', 'memori', 'warna', 'kondisi', 'harga_per_jam', 'harga_per_hari', 'specs', 'isEditing', 'is_active']);
        $this->category_id = '';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;
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
        if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'staff'])) return;
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

        $unit = Unit::updateOrCreate(
            ['id' => $this->unit_id],
            [
                'category_id' => $this->category_id,
                'seri' => $this->seri,
                'imei' => $isIphone ? $this->imei : null,
                'memori' => $isIphone ? $this->memori : null,
                'warna' => $isIphone ? $this->warna : null,
                'kondisi' => $this->kondisi,
                'specs' => $this->specs,
                'harga_per_jam' => $this->harga_per_jam,
                'harga_per_hari' => $this->harga_per_hari,
                'is_active' => $this->is_active,
            ]
        );

        $action = $this->unit_id ? 'edit_unit' : 'create_unit';
        $this->logActivity($action, $unit, "Mengelola data unit: {$unit->seri}");

        $this->showModal = false;
    }

    public function delete($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $unit = Unit::findOrFail($id);
        $unit->delete();
        $this->logActivity('delete_unit', $unit, "Memindahkan unit ke tempat sampah: {$unit->seri}");
        session()->flash('message', 'Unit dipindahkan ke tempat sampah.');
    }

    public function restoreUnit($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $unit = Unit::withTrashed()->findOrFail($id);
        $unit->restore();
        $this->logActivity('restore_unit', $unit, "Memulihkan unit: {$unit->seri}");
        session()->flash('message', 'Unit berhasil dipulihkan.');
    }

    public function forceDelete($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $unit = Unit::withTrashed()->findOrFail($id);
        $name = $unit->seri;
        $unit->forceDelete();
        $this->logActivity('force_delete_unit', null, "Menghapus permanen unit: {$name}");
        session()->flash('message', 'Unit dihapus secara permanen.');
    }
    public function showQr($id)
    {
        $this->selectedUnitForQr = Unit::withTrashed()->findOrFail($id);
        $this->showQrModal = true;
    }

    // --- Category Management Methods ---
    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage('unitsPage');
        $this->resetPage('catsPage');
    }

    public function updatedCatName($value)
    {
        if (!$this->isEditingCat) {
            $this->cat_slug = \Illuminate\Support\Str::slug($value);
        }
    }

    public function createCat()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $this->reset(['cat_id', 'cat_name', 'cat_slug', 'cat_icon', 'cat_fields', 'isEditingCat']);
        $this->showCatModal = true;
    }

    public function editCat($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $category = \App\Models\Category::findOrFail($id);
        $this->cat_id = $category->id;
        $this->cat_name = $category->name;
        $this->cat_slug = $category->slug;
        $this->cat_icon = $category->icon;
        $this->cat_fields = $category->custom_fields ?? [];
        $this->isEditingCat = true;
        $this->showCatModal = true;
    }

    public function addCatField()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $this->cat_fields[] = '';
    }

    public function removeCatField($index)
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        unset($this->cat_fields[$index]);
        $this->cat_fields = array_values($this->cat_fields);
    }

    public function saveCat()
    {
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) return;
        $this->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'required|string|max:255|unique:categories,slug,' . $this->cat_id,
            'cat_icon' => 'nullable|string|max:255',
            'cat_fields.*' => 'nullable|string|max:255',
        ]);

        $fields = array_values(array_filter($this->cat_fields, fn($f) => !empty($f)));

        $cat = \App\Models\Category::updateOrCreate(
            ['id' => $this->cat_id],
            [
                'name' => $this->cat_name,
                'slug' => $this->cat_slug,
                'icon' => $this->cat_icon,
                'custom_fields' => $fields,
            ]
        );

        $this->logActivity('manage_category', $cat, "Mengelola kategori: {$cat->name}");

        $this->showCatModal = false;
        session()->flash('message', 'Kategori berhasil disimpan.');
    }

    public function deleteCat($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $exists = Unit::where('category_id', $id)->exists();
        if ($exists) {
            session()->flash('error', 'Tidak bisa menghapus kategori yang masih memiliki unit.');
            return;
        }

        \App\Models\Category::findOrFail($id)->delete();
        session()->flash('message', 'Kategori berhasil dihapus.');
    }

    public function render()
    {
        $categoriesQuery = \App\Models\Category::orderBy('name');
        
        if ($this->activeTab === 'categories' && $this->search) {
            $categoriesQuery->where('name', 'like', '%' . $this->search . '%');
        }

        $unitsQuery = Unit::query()
            ->with('category')
            ->when($this->filterStatus === 'trashed', fn($q) => $q->onlyTrashed())
            ->when($this->filterStatus !== 'trashed', fn($q) => $q->withoutTrashed())
            ->when($this->search && $this->activeTab === 'units', function($q) {
                $search = $this->search;
                return $q->where(function($qq) use ($search) {
                    if (is_numeric($search)) {
                        $qq->where('id', $search)
                           ->orWhere('imei', 'like', '%' . $search . '%');
                    } else {
                        $qq->where('seri', 'like', '%' . $search . '%')
                           ->orWhere('imei', 'like', '%' . $search . '%')
                           ->orWhere('warna', 'like', '%' . $search . '%');
                    }
                });
            })
            ->when($this->filterKategori, fn($q) => $q->where('category_id', $this->filterKategori))
            ->when($this->filterStatus !== '' && $this->filterStatus !== 'trashed', function ($q) {
                if ($this->filterStatus === 'active')
                    return $q->where('is_active', true);
                if ($this->filterStatus === 'inactive')
                    return $q->where('is_active', false);
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('seri', 'asc');

        return view('livewire.admin.unit-manager', [
            'units' => $unitsQuery->paginate($this->perPage, ['*'], 'unitsPage'),
            'categories' => $categoriesQuery->paginate($this->perPage, ['*'], 'catsPage'),
            'all_categories' => \App\Models\Category::orderBy('name')->get() // For the dropdowns
        ])->layout('layouts.admin');
    }
}