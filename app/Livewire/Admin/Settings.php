<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Settings extends Component
{
    use WithFileUploads;

    public $qris_photo;
    public $hero_photo;
    public $qris;
    public $hero;
    
    public $users = [];
    public $name = '', $email = '', $password = '', $role = 'admin';

    public $home_title = '', $home_description = '', $late_tolerance_minutes = 60;
    public $admin_wa = '', $admin_address = '', $terms_conditions = '';
    public $payment_methods = ['qris' => true, 'cash' => true, 'transfer' => false];
    public $about_faq_items = [];

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Fitur ini khusus Admin Utama.');
        }

        $this->loadUsers();
        $this->home_title = \App\Models\Setting::getVal('home_title', 'Sewa iPhone Mudah, Cepat, dan Aman');
        $this->home_description = \App\Models\Setting::getVal('home_description', 'Pilih tipe iPhone sesuai kebutuhan Anda. Bebas atur jadwal sewa, harga bersahabat, tanpa syarat ribet!');
        $this->late_tolerance_minutes = \App\Models\Setting::getVal('late_tolerance_minutes', 60);
        $this->admin_wa = \App\Models\Setting::getVal('admin_wa', '6281234567890');
        $this->admin_address = \App\Models\Setting::getVal('admin_address', 'Jl. Jend. Sudirman, Purwokerto');
        $defaultTerms = "1. Penyewa wajib menjaga iPhone yang disewa dan bertanggung jawab atas kerusakan atau kehilangan selama masa sewa.\n2. Pembayaran dilakukan di awal sebelum unit diserahkan, sesuai total tagihan yang tertera.\n3. Keterlambatan pengembalian melewati batas toleransi waktu akan dikenakan denda yang ditentukan oleh pengelola.\n4. Pengelola berhak menolak penyewaan apabila dokumen identitas (NIK/KTP) tidak valid atau tidak sesuai.\n5. Pemesanan yang sudah terkonfirmasi tidak dapat dibatalkan secara sepihak oleh penyewa.";
        $this->terms_conditions = \App\Models\Setting::getVal('terms_conditions', $defaultTerms);
        $savedPayment = \App\Models\Setting::getVal('payment_methods', json_encode(['qris' => true, 'cash' => true, 'transfer' => false]));
        $this->payment_methods = json_decode($savedPayment, true) ?: ['qris' => true, 'cash' => true, 'transfer' => false];

        $savedFaq = \App\Models\Setting::getVal('about_faq_items', json_encode([]));
        $this->about_faq_items = json_decode($savedFaq, true) ?: [];

        $this->qris = \App\Models\Setting::getVal('qris', 'default.jpg');
        $this->hero = \App\Models\Setting::getVal('hero', 'default.jpg');
    }

    public function loadUsers()
    {
        $this->users = \App\Models\User::all();
    }

    public function createUser()
    {
        if (auth()->user()->role !== 'admin') return;

        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4',
            'role' => 'required|in:admin,viewer'
        ]);

        \App\Models\User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => \Illuminate\Support\Facades\Hash::make($this->password),
            'role' => $this->role
        ]);

        $this->reset(['name', 'email', 'password', 'role']);
        $this->loadUsers();
        session()->flash('user_message', 'Akun Admin berhasil ditambahkan');
    }

    public function deleteUser($id)
    {
        if (auth()->user()->role !== 'admin') return;

        if (\App\Models\User::count() > 1 && auth()->id() != $id) {
            \App\Models\User::findOrFail($id)->delete();
            $this->loadUsers();
            session()->flash('user_message', 'Akun berhasil dihapus.');
        } else {
            session()->flash('user_error', 'Tidak bisa menghapus akun sendiri atau admin terakhir.');
        }
    }

    public function saveGeneralSettings()
    {
        $this->validate([
            'home_title' => 'required',
            'home_description' => 'required',
            'late_tolerance_minutes' => 'required|numeric|min:0',
            'admin_wa' => 'required',
            'admin_address' => 'required'
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'home_title'], ['value' => $this->home_title]);
        \App\Models\Setting::updateOrCreate(['key' => 'home_description'], ['value' => $this->home_description]);
        \App\Models\Setting::updateOrCreate(['key' => 'late_tolerance_minutes'], ['value' => $this->late_tolerance_minutes]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_wa'], ['value' => $this->admin_wa]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_address'], ['value' => $this->admin_address]);
        \App\Models\Setting::updateOrCreate(['key' => 'terms_conditions'], ['value' => $this->terms_conditions]);
        \App\Models\Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($this->payment_methods)]);

        session()->flash('general_message', 'Pengaturan Umum berhasil disimpan.');
    }

    public function addFaq()
    {
        $this->about_faq_items[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq($index)
    {
        unset($this->about_faq_items[$index]);
        $this->about_faq_items = array_values($this->about_faq_items);
    }

    public function saveFaqSettings()
    {
        if (auth()->user()->role !== 'admin') return;

        \App\Models\Setting::updateOrCreate(
            ['key' => 'about_faq_items'], 
            ['value' => json_encode($this->about_faq_items)]
        );

        session()->flash('faq_message', 'Konten Halaman Tentang (FAQ) berhasil disimpan.');
    }

    public function saveHero()
    {
        if (auth()->user()->role !== 'admin') return;

        $this->validate([
            'hero_photo' => 'required|image|max:3072|mimes:jpg,jpeg,png,webp',
        ]);

        $filename = 'hero_' . time() . '.' . $this->hero_photo->getClientOriginalExtension();

        if (!file_exists(public_path('uploads'))) {
            mkdir(public_path('uploads'), 0777, true);
        }

        // Use standard PHP copy as it's more reliable across different volumes
        copy($this->hero_photo->getRealPath(), public_path('uploads/' . $filename));

        \App\Models\Setting::updateOrCreate(
            ['key' => 'hero'],
            ['value' => $filename]
        );

        $this->hero = $filename;

        session()->flash('hero_message', '1:1 Foto Beranda berhasil diperbarui!');
    }

    public function saveQris()
    {
        $this->validate([
            'qris_photo' => 'required|image|max:2048', // 2MB Max
        ]);

        $filename = 'qris_' . time() . '.' . $this->qris_photo->getClientOriginalExtension();

        if (!file_exists(public_path('uploads'))) {
            mkdir(public_path('uploads'), 0777, true);
        }

        // Use standard PHP copy as it's more reliable across different volumes
        copy($this->qris_photo->getRealPath(), public_path('uploads/' . $filename));

        \App\Models\Setting::updateOrCreate(
            ['key' => 'qris'],
            ['value' => $filename]
        );

        $this->qris = $filename;
        
        session()->flash('message', 'QRIS Photo successfully updated.');
    }

    public function render()
    {
        return view('livewire.admin.settings')->layout('layouts.admin');
    }
}
