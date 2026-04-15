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
    public $social_ig_url = '', $social_ig_name = '', $social_tiktok_url = '', $social_tiktok_name = '';
    
    public $importFile;

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

        $this->social_ig_url = \App\Models\Setting::getVal('social_ig_url', '');
        $this->social_ig_name = \App\Models\Setting::getVal('social_ig_name', '');
        $this->social_tiktok_url = \App\Models\Setting::getVal('social_tiktok_url', '');
        $this->social_tiktok_name = \App\Models\Setting::getVal('social_tiktok_name', '');
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
        \App\Models\Setting::updateOrCreate(['key' => 'social_ig_url'], ['value' => $this->social_ig_url]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_ig_name'], ['value' => $this->social_ig_name]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_tiktok_url'], ['value' => $this->social_tiktok_url]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_tiktok_name'], ['value' => $this->social_tiktok_name]);

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

    public function exportData()
    {
        if (auth()->user()->role !== 'admin') return;

        $data = [
            'version' => '1.0',
            'exported_at' => now()->toDateTimeString(),
            'units' => \App\Models\Unit::withTrashed()->get(),
            'pricing_rules' => \App\Models\PricingRule::withTrashed()->get(),
            'rentals' => \App\Models\Rental::all(),
            'settings' => \App\Models\Setting::all(),
            'users' => \App\Models\User::all(),
        ];

        $filename = 'backup_rentspace_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename);
    }

    public function importData()
    {
        if (auth()->user()->role !== 'admin') return;

        $this->validate([
            'importFile' => 'required|mimes:json|max:10240', // 10MB Max
        ]);

        try {
            $jsonContent = file_get_contents($this->importFile->getRealPath());
            $data = json_decode($jsonContent, true);

            if (!$data || !isset($data['version'])) {
                session()->flash('import_error', 'File backup tidak valid atau format salah.');
                return;
            }

            // Disable foreign keys for SQLite outside the transaction
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
            
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                    if (isset($data['settings'])) {
                        \Illuminate\Support\Facades\DB::table('settings')->delete();
                        foreach ($data['settings'] as $row) {
                            \Illuminate\Support\Facades\DB::table('settings')->insert($row);
                        }
                    }

                    if (isset($data['units'])) {
                        \Illuminate\Support\Facades\DB::table('units')->delete();
                        foreach ($data['units'] as $row) {
                            \Illuminate\Support\Facades\DB::table('units')->insert($row);
                        }
                    }

                    if (isset($data['pricing_rules'])) {
                        \Illuminate\Support\Facades\DB::table('pricing_rules')->delete();
                        foreach ($data['pricing_rules'] as $row) {
                            \Illuminate\Support\Facades\DB::table('pricing_rules')->insert($row);
                        }
                    }

                    if (isset($data['rentals'])) {
                        \Illuminate\Support\Facades\DB::table('rentals')->delete();
                        foreach ($data['rentals'] as $row) {
                            \Illuminate\Support\Facades\DB::table('rentals')->insert($row);
                        }
                    }

                    if (isset($data['users'])) {
                        foreach ($data['users'] as $row) {
                            \Illuminate\Support\Facades\DB::table('users')->updateOrInsert(
                                ['email' => $row['email']],
                                \Illuminate\Support\Arr::except($row, ['id'])
                            );
                        }
                    }
                });
            } finally {
                \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
            }

            session()->flash('import_message', 'Data berhasil dipulihkan dari cadangan!');
            $this->reset('importFile');
            $this->mount(); // Refresh local properties
        } catch (\Exception $e) {
            session()->flash('import_error', 'Gagal memproses file: ' . $e->getMessage());
        }
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
