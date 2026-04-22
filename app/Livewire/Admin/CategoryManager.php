<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Component;
use Illuminate\Support\Str;

class CategoryManager extends Component
{
    public $category_id, $name, $slug, $icon;
    public $fields = []; // For dynamic custom fields definition
    public $isEditing = false;
    public $showModal = false;
    public $search = '';

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if (!in_array(auth()->user()->role, ['admin', 'viewer'])) {
            abort(403);
        }
    }

    public function updatedName($value)
    {
        if (!$this->isEditing) {
            $this->slug = Str::slug($value);
        }
    }

    public function addField()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->fields[] = '';
    }

    public function removeField($index)
    {
        if (auth()->user()->role !== 'admin') return;
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->reset(['category_id', 'name', 'slug', 'icon', 'fields', 'isEditing']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') return;
        $category = Category::findOrFail($id);
        $this->category_id = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->icon = $category->icon;
        $this->fields = $category->custom_fields ?? [];
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $this->category_id,
            'icon' => 'nullable|string|max:255',
            'fields.*' => 'nullable|string|max:255',
        ]);

        // Filter out empty fields
        $fields = array_values(array_filter($this->fields, fn($f) => !empty($f)));

        Category::updateOrCreate(
            ['id' => $this->category_id],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'icon' => $this->icon,
                'custom_fields' => $fields,
            ]
        );

        $this->showModal = false;
        $this->dispatch('refresh');
    }

    public function delete($id)
    {
        if (auth()->user()->role !== 'admin') return;
        // Prevent deleting if category has units?
        $category = Category::findOrFail($id);
        
        // Optional: Check if units exist
        $exists = \App\Models\Unit::where('category_id', $id)->exists();
        if ($exists) {
            session()->flash('error', 'Tidak bisa menghapus kategori yang masih memiliki unit.');
            return;
        }

        $category->delete();
    }

    public function render()
    {
        $categories = Category::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.category-manager', [
            'categories' => $categories
        ])->layout('layouts.admin');
    }
}
