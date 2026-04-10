<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public $qris_photo;
    
    public $users = [];
    public $name = '', $email = '', $password = '';

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = \App\Models\User::all();
    }

    public function createUser()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4'
        ]);

        \App\Models\User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => \Illuminate\Support\Facades\Hash::make($this->password)
        ]);

        $this->reset(['name', 'email', 'password']);
        $this->loadUsers();
        session()->flash('user_message', 'Akun Admin berhasil ditambahkan');
    }

    public function deleteUser($id)
    {
        if (\App\Models\User::count() > 1 && auth()->id() != $id) {
            \App\Models\User::findOrFail($id)->delete();
            $this->loadUsers();
            session()->flash('user_message', 'Akun berhasil dihapus.');
        } else {
            session()->flash('user_error', 'Tidak bisa menghapus akun sendiri atau admin terakhir.');
        }
    }

    public function saveQris()
    {
        $this->validate([
            'qris_photo' => 'required|image|max:2048', // 2MB Max
        ]);

        $this->qris_photo->storeAs('public', 'qris.jpg');
        
        session()->flash('message', 'QRIS Photo successfully updated.');
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.admin');
    }
}
