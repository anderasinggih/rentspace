<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

use Livewire\Attributes\Url;

class Settings extends Component
{
    use WithFileUploads, WithPagination;

    public $qris_photo;
    public $hero_photo;
    public $hero2_photo;
    public $hero3_photo;
    public $qris;
    public $hero;
    public $hero2;
    public $hero3;
    public $perPage = 10;

    public $editingUserId = null;
    public $isEditMode = false;
    public $name = '', $email = '', $password = '', $role = 'admin';
    public $is_also_affiliate = false;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    #[Url(as: 'tab')]
    public $activeTab = 'akun';
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    // Affiliator Profile Fields
    public $affiliate_no_hp = '', $affiliate_nik = '', $affiliate_alamat = '';
    public $affiliate_referral_code = '', $affiliate_commission_rate = 10;
    public $affiliate_bank_name = '', $affiliate_bank_account_number = '', $affiliate_bank_account_name = '';

    public $home_title = '', $home_description = '', $late_tolerance_minutes = 60;
    public $admin_wa = '', $admin_address = '', $terms_conditions = '';
    public $payment_methods = [
        'qris' => true, 
        'manual_qris' => false,
        'cash' => true, 
        'bca' => true, 
        'mandiri' => true, 
        'bni' => true, 
        'bri' => true, 
        'permata' => true,
        'bsi' => true,
        'cimb' => true
    ];
    public $about_faq_items = [];
    public $social_ig_url = '', $social_ig_name = '', $social_tiktok_url = '', $social_tiktok_name = '';
    public $min_payout = 50000;
     public $is_maintenance = false;
    public $maintenance_message = 'Kami akan segera kembali!';
    public $is_email_active = true;
    public $is_user_email_active = true;
    public $admin_email_recipients = '';

    // Reminder & Overdue Email Settings
    public $is_reminder_active = true;
    public $reminder_hours_before = 2;
    public $is_overdue_active = true;
    public $overdue_minutes_after = 15;

    // Greetings Properties
    public $greeting_morning = '', $greeting_day = '', $greeting_afternoon = '', $greeting_evening = '', $greeting_night = '';
    public $is_greeting_active = true;

    public $is_chatbot_active = true;
    public $chatbot_api_key = '';
    public $chatbot_model = \App\Services\GeminiAIService::DEFAULT_MODEL;
    public $chatbot_tpm_limit = '300000';
    public $chatbot_report_data_limit = '0';

    // Slot 2-4 untuk failover. Google menghitung kuota per project, jadi
    // slot tambahan ini hanya membantu kalau kuncinya dibuat di project berbeda.
    public $chatbot_api_key_2 = '';
    public $chatbot_api_key_3 = '';
    public $chatbot_api_key_4 = '';

    // API key & model dipisah per fitur supaya kuota tiap fitur bisa diatur
    // dan diamankan terpisah (lihat GeminiAIService::apiKeyFor).
    public $report_api_key = '';
    public $report_model = \App\Services\GeminiAIService::DEFAULT_MODEL;
    public $broadcast_api_key = '';
    public $broadcast_model = \App\Services\GeminiAIService::DEFAULT_MODEL;
    public $ai_key_test = [];   // hasil tombol "Tes" per fitur
    public $ai_key_test_slot = []; // hasil tes per slot kunci: "customer_3" => hasil
    public $testing_ai_feature = null;
    public $aiSettingsMessage = null; // ['ok' => bool, 'text' => string] — hanya tampil di tab WhatsApp

    public $admin_wa_secondary = '';
    public $admin_wa_group_id = '';
    public $admin_report_group_id = '';
    public $admin_notify_group_id = '';
    public $chatbot_custom_knowledge = [];
    public $new_knowledge_key = '';
    public $new_knowledge_value = '';

    // WhatsApp Broadcast Properties
    // WhatsApp Broadcast Properties
    public $broadcast_groups = [];
    public $broadcast_shortcuts = [];
    public $new_group_name = '';
    public $new_group_numbers = '';
    public $editing_group_index = null; // null jika mode tambah baru, index jika edit
    public $editing_group_id = null;
    public $customer_search_query = ''; // pencarian customer untuk ngetag 1 per 1
    public $selected_customer_tags = []; // array of ['phone' => '...', 'name' => '...']
    public $new_shortcut_code = '';
    public $new_shortcut_title = '';
    public $new_shortcut_message = '';
    public $active_broadcast_group = '';
    public $active_broadcast_message = '';
    public $is_sending_broadcast = false;
    public $broadcast_delay_mode = 'safe'; // 'safe' (2-4s random delay + pause) or 'normal' (1-2s)
    public $import_filter_unit_id = '';
    public $import_filter_status = '';
    public $broadcast_ai_brief = '';
    public $broadcast_ai_tone = 'promosi';
    public $is_generating_broadcast = false;

    public $onesignal_app_id = '';
    public $onesignal_rest_api_key = '';
    public $onesignal_safari_web_id = '';

    public $importFile;

    public function mount()
    {
        if (!in_array(auth()->user()->role, ['admin', 'viewer'])) {
            abort(403, 'Akses ditolak.');
        }
        $this->home_title = \App\Models\Setting::getVal('home_title', 'Sewa iPhone Mudah, Cepat, dan Aman');
        $this->home_description = \App\Models\Setting::getVal('home_description', 'Pilih tipe iPhone sesuai kebutuhan Anda. Bebas atur jadwal sewa, harga bersahabat, tanpa syarat ribet!');
        $this->late_tolerance_minutes = \App\Models\Setting::getVal('late_tolerance_minutes', 60);
        $this->admin_wa = \App\Models\Setting::getVal('admin_wa', '6281234567890');
        $this->admin_address = \App\Models\Setting::getVal('admin_address', 'Jl. Jend. Sudirman, Purwokerto');
        $defaultTerms = "1. Penyewa wajib menjaga iPhone yang disewa dan bertanggung jawab atas kerusakan atau kehilangan selama masa sewa.\n2. Pembayaran dilakukan di awal sebelum unit diserahkan, sesuai total tagihan yang tertera.\n3. Keterlambatan pengembalian melewati batas toleransi waktu akan dikenakan denda yang ditentukan oleh pengelola.\n4. Pengelola berhak menolak penyewaan apabila dokumen identitas (NIK/KTP) tidak valid atau tidak sesuai.\n5. Pemesanan yang sudah terkonfirmasi tidak dapat dibatalkan secara sepihak oleh penyewa.";
        $this->terms_conditions = \App\Models\Setting::getVal('terms_conditions', $defaultTerms);
        $defaultPayments = json_encode([
            'qris' => true, 'manual_qris' => false, 'cash' => true, 'bca' => true, 'mandiri' => true, 
            'bni' => true, 'bri' => true, 'permata' => true, 'bsi' => true, 'cimb' => true
        ]);
        $savedPayment = \App\Models\Setting::getVal('payment_methods', $defaultPayments);
        $this->payment_methods = json_decode($savedPayment, true) ?: json_decode($defaultPayments, true);

        $savedFaq = \App\Models\Setting::getVal('about_faq_items', json_encode([]));
        $this->about_faq_items = json_decode($savedFaq, true) ?: [];

        $this->qris = \App\Models\Setting::getVal('qris', 'default.jpg');
        $this->hero = \App\Models\Setting::getVal('hero', 'default.jpg');
        $this->hero2 = \App\Models\Setting::getVal('hero2', 'default2.jpg');
        $this->hero3 = \App\Models\Setting::getVal('hero3', 'default3.jpg');
        $this->min_payout = \App\Models\Setting::getVal('min_payout', 50000);

        $this->social_ig_url = \App\Models\Setting::getVal('social_ig_url', '');
        $this->social_ig_name = \App\Models\Setting::getVal('social_ig_name', '');
        $this->social_tiktok_url = \App\Models\Setting::getVal('social_tiktok_url', '');
        $this->social_tiktok_name = \App\Models\Setting::getVal('social_tiktok_name', '');

        $this->is_maintenance = \App\Models\Setting::getVal('is_maintenance', '0') == '1';
        $this->maintenance_message = \App\Models\Setting::getVal('maintenance_message', 'Kami akan segera kembali! Saat ini sistem sedang dalam pemeliharaan rutin untuk meningkatkan layanan kami.');

        // Load Greetings
        $this->greeting_morning = \App\Models\Setting::getVal('greeting_morning', 'Pagi Bos! ⚡️ Semangat harinya, jangan lupa bawa iPhone RentSpace buat momen spesialmu.');
        $this->greeting_day = \App\Models\Setting::getVal('greeting_day', 'Siang Bos! ☀️ Panas ya? Tetep tampil kece & profesional bareng iPhone dari RentSpace.');
        $this->greeting_afternoon = \App\Models\Setting::getVal('greeting_afternoon', 'Sore Bos! ☁️ Purwokerto mulai sejuk nih, asik banget buat bikin konten cinematic.');
        $this->greeting_evening = \App\Models\Setting::getVal('greeting_evening', 'Malam Bos! ✨ Butuh iPhone buat dinner atau event keren malam ini? Kami ready!');
        $this->greeting_night = \App\Models\Setting::getVal('greeting_night', 'Masih bangun Bos? 🌙 Lagi nyari unit buat dipake besok ya? Langsung sikat!');
        // Load Email Settings
        $this->is_email_active = \App\Models\Setting::getVal('is_email_active', '1') == '1';
        $this->is_user_email_active = \App\Models\Setting::getVal('is_user_email_active', '1') == '1';
        $this->admin_email_recipients = \App\Models\Setting::getVal('admin_email_recipients', config('mail.admin_email') ?: '');
        
        // Reminder Settings
        $this->is_reminder_active = \App\Models\Setting::getVal('is_reminder_active', '1') == '1';
        $this->reminder_hours_before = \App\Models\Setting::getVal('reminder_hours_before', '2');
        $this->is_overdue_active = \App\Models\Setting::getVal('is_overdue_active', '1') == '1';
        $this->overdue_minutes_after = \App\Models\Setting::getVal('overdue_minutes_after', '15');
        
        $this->is_greeting_active = \App\Models\Setting::getVal('is_greeting_active', '1') == '1';
        
        // Load Chatbot Settings
        $this->is_chatbot_active = \App\Models\Setting::getVal('is_chatbot_active', '1') == '1';
        $this->chatbot_api_key = \App\Models\Setting::getVal('chatbot_api_key', config('services.gemini.key') ?: '');
        $this->chatbot_model = \App\Models\Setting::getVal('chatbot_model', \App\Services\GeminiAIService::DEFAULT_MODEL);
        $this->chatbot_tpm_limit = (string) \App\Models\Setting::getVal('chatbot_tpm_limit', '300000');
        $this->chatbot_report_data_limit = (string) \App\Models\Setting::getVal('chatbot_report_data_limit', '0');

        for ($slot = 2; $slot <= \App\Services\GeminiAIService::KEY_POOL_SIZE; $slot++) {
            $prop = \App\Services\GeminiAIService::keySlotName('customer', $slot);
            $this->{$prop} = (string) \App\Models\Setting::getVal(
                \App\Services\GeminiAIService::keySlotName('customer', $slot),
                ''
            );
        }

        // Key & model laporan & broadcast. Kosong = otomatis pakai key customer,
        // jadi instalasi lama yang hanya punya satu key tidak ikut rusak.
        $this->report_api_key = (string) \App\Models\Setting::getVal('report_api_key', '');
        $this->report_model = (string) \App\Models\Setting::getVal('report_model', \App\Services\GeminiAIService::DEFAULT_MODEL);
        $this->broadcast_api_key = (string) \App\Models\Setting::getVal('broadcast_api_key', '');
        $this->broadcast_model = (string) \App\Models\Setting::getVal('broadcast_model', \App\Services\GeminiAIService::DEFAULT_MODEL);
        $this->ai_key_test = [];
        $this->ai_key_test_slot = [];
        $this->admin_wa_secondary = \App\Models\Setting::getVal('admin_wa_secondary', '');
        $this->admin_wa_group_id = \App\Models\Setting::getVal('admin_wa_group_id', '');
        $this->admin_report_group_id = \App\Models\Setting::getVal('admin_report_group_id', '');
        $this->admin_notify_group_id = \App\Models\Setting::getVal('admin_notify_group_id', '');
        $rawKnowledge = \App\Models\Setting::getVal('chatbot_custom_knowledge', '[]');
        $this->chatbot_custom_knowledge = json_decode($rawKnowledge, true) ?: [];

        // Load Broadcast Settings
        $rawGroups = \App\Models\Setting::getVal('wa_broadcast_groups', '[]');
        $this->broadcast_groups = json_decode($rawGroups, true) ?: [];
        $rawShortcuts = \App\Models\Setting::getVal('wa_broadcast_shortcuts', '[]');
        $this->broadcast_shortcuts = json_decode($rawShortcuts, true) ?: [
            [
                'code' => 'promo_weekend',
                'title' => 'Promo Weekend',
                'message' => "Halo Kak! 🎉\nWeekend ini ada promo spesial sewa iPhone di Rent Space Purwokerto.\nBooking sekarang sebelum unit habis: https://rentspacepurwokerto.my.id/booking"
            ],
            [
                'code' => 'info_unit_baru',
                'title' => 'Ready Unit Baru',
                'message' => "Halo Kak! ✨\nKabar gembira, unit baru sudah ready di Rent Space Purwokerto.\nCek katalog & reservasi langsung di: https://rentspacepurwokerto.my.id/booking"
            ]
        ];

        // Load OneSignal Settings
        $this->onesignal_app_id = \App\Models\Setting::getVal('onesignal_app_id', '');
        $this->onesignal_rest_api_key = \App\Models\Setting::getVal('onesignal_rest_api_key', '');
        $this->onesignal_safari_web_id = \App\Models\Setting::getVal('onesignal_safari_web_id', '');
    }

    // Removed loadUsers() to use paginate in render()

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function createUser()
    {
        if (auth()->user()->role !== 'admin')
            return;

        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:4',
            'role' => 'required|in:admin,viewer,affiliator,staff'
        ]);

        $user = \App\Models\User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => \Illuminate\Support\Facades\Hash::make($this->password),
            'role' => $this->role
        ]);

        // If role is affiliator, ensure profile is created
        if ($this->role === 'affiliator') {
            \App\Models\AffiliatorProfile::create([
                'user_id' => $user->id,
                'referral_code' => \App\Models\AffiliatorProfile::generateCode($user->name),
                'commission_rate' => 10,
                'status' => 'approved'
            ]);
        }

        $this->reset(['name', 'email', 'password', 'role']);
        session()->flash('user_message', 'Akun berhasil ditambahkan');
    }

    public function editUser($id)
    {
        if (auth()->user()->role !== 'admin') return;
        
        $user = \App\Models\User::findOrFail($id);
        $this->editingUserId = $id;
        $this->isEditMode = true;
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = ''; // Clear password field

        // Load Affiliate Profile if exists
        $profile = $user->affiliateProfile;
        if ($profile) {
            $this->is_also_affiliate = true;
            $this->affiliate_no_hp = $profile->no_hp;
            $this->affiliate_nik = $profile->nik;
            $this->affiliate_alamat = $profile->alamat;
            $this->affiliate_referral_code = $profile->referral_code;
            $this->affiliate_commission_rate = $profile->commission_rate;
            $this->affiliate_bank_name = $profile->bank_name;
            $this->affiliate_bank_account_number = $profile->bank_account_number;
            $this->affiliate_bank_account_name = $profile->bank_account_name;
        } else {
            $this->is_also_affiliate = ($this->role === 'affiliator');
            $this->resetAffiliateFields();
        }
    }

    private function resetAffiliateFields()
    {
        $this->reset([
            'affiliate_no_hp', 'affiliate_nik', 'affiliate_alamat',
            'affiliate_referral_code', 'affiliate_commission_rate',
            'affiliate_bank_name', 'affiliate_bank_account_number', 'affiliate_bank_account_name'
        ]);
        $this->affiliate_commission_rate = 10;
    }

    public function updateUser()
    {
        if (auth()->user()->role !== 'admin' || !$this->editingUserId) return;

        $rules = [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => 'required|in:admin,viewer,affiliator,staff',
            'password' => 'nullable|min:4'
        ];

        if ($this->role === 'affiliator' || $this->is_also_affiliate) {
            $rules['affiliate_no_hp'] = 'required';
            $rules['affiliate_referral_code'] = 'required|alpha_num|unique:affiliator_profiles,referral_code,' . $this->editingUserId . ',user_id';
            $rules['affiliate_commission_rate'] = 'required|numeric|min:0|max:100';
        }

        $this->validate($rules);

        $user = \App\Models\User::findOrFail($this->editingUserId);
        $user->name = $this->name;
        $user->email = $this->email;
        $user->role = $this->role;

        if ($this->password) {
            $user->password = \Illuminate\Support\Facades\Hash::make($this->password);
        }

        $user->save();

        // Sync Affiliate Profile
        if ($this->role === 'affiliator' || $this->is_also_affiliate) {
            \App\Models\AffiliatorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'no_hp' => $this->affiliate_no_hp,
                    'nik' => $this->affiliate_nik,
                    'alamat' => $this->affiliate_alamat,
                    'referral_code' => strtoupper($this->affiliate_referral_code),
                    'commission_rate' => $this->affiliate_commission_rate,
                    'bank_name' => $this->affiliate_bank_name,
                    'bank_account_number' => $this->affiliate_bank_account_number,
                    'bank_account_name' => $this->affiliate_bank_account_name,
                    'status' => 'approved' // Set approved by default if created by admin
                ]
            );
        } else {
            // Optional: Delete profile if no longer an affiliate?
            // Decided to keep it but user can deactivate in separate logic.
        }

        $this->cancelEdit();
        session()->flash('user_message', 'Akun berhasil diperbarui.');
    }

    public function cancelEdit()
    {
        $this->reset(['editingUserId', 'isEditMode', 'name', 'email', 'password', 'role', 'is_also_affiliate']);
        $this->resetAffiliateFields();
        $this->role = 'admin';
        $this->resetValidation();
    }
 
    public function deleteUser($id)
    {
        if (auth()->user()->role !== 'admin')
            return;

        if (\App\Models\User::count() > 1 && auth()->id() != $id) {
            \App\Models\User::findOrFail($id)->delete();
            session()->flash('user_message', 'Akun berhasil dihapus.');
        } else {
            session()->flash('user_error', 'Tidak bisa menghapus akun sendiri atau admin terakhir.');
        }
    }

    public function saveGeneralSettings()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'home_title' => 'required',
            'home_description' => 'required',
            'late_tolerance_minutes' => 'required|numeric|min:0',
            'admin_wa' => 'required',
            'admin_address' => 'required',
        ]);
        $this->validateAiFeatureSettings();

        \App\Models\Setting::updateOrCreate(['key' => 'home_title'], ['value' => $this->home_title]);
        \App\Models\Setting::updateOrCreate(['key' => 'home_description'], ['value' => $this->home_description]);
        \App\Models\Setting::updateOrCreate(['key' => 'late_tolerance_minutes'], ['value' => $this->late_tolerance_minutes]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_wa'], ['value' => $this->admin_wa]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_address'], ['value' => $this->admin_address]);
        \App\Models\Setting::updateOrCreate(['key' => 'terms_conditions'], ['value' => $this->terms_conditions]);
        \App\Models\Setting::updateOrCreate(['key' => 'min_payout'], ['value' => $this->min_payout]);
        \App\Models\Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($this->payment_methods)]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_ig_url'], ['value' => $this->social_ig_url]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_ig_name'], ['value' => $this->social_ig_name]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_tiktok_url'], ['value' => $this->social_tiktok_url]);
        \App\Models\Setting::updateOrCreate(['key' => 'social_tiktok_name'], ['value' => $this->social_tiktok_name]);
        \App\Models\Setting::updateOrCreate(['key' => 'is_email_active'], ['value' => $this->is_email_active ? '1' : '0']);
        \App\Models\Setting::updateOrCreate(['key' => 'is_user_email_active'], ['value' => $this->is_user_email_active ? '1' : '0']);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_email_recipients'], ['value' => $this->admin_email_recipients]);

        // Save Reminder & Overdue Settings
        \App\Models\Setting::updateOrCreate(['key' => 'is_reminder_active'], ['value' => $this->is_reminder_active ? '1' : '0']);
        \App\Models\Setting::updateOrCreate(['key' => 'reminder_hours_before'], ['value' => $this->reminder_hours_before]);
        \App\Models\Setting::updateOrCreate(['key' => 'is_overdue_active'], ['value' => $this->is_overdue_active ? '1' : '0']);
        \App\Models\Setting::updateOrCreate(['key' => 'overdue_minutes_after'], ['value' => $this->overdue_minutes_after]);

        // Save Chatbot Settings
        \App\Models\Setting::updateOrCreate(['key' => 'is_chatbot_active'], ['value' => $this->is_chatbot_active ? '1' : '0']);
        $this->persistAiFeatureSettings();
        $this->ai_key_test = [];
        $this->ai_key_test_slot = [];
        $this->aiSettingsMessage = ['ok' => true, 'text' => 'Pengaturan AI tersimpan (kunci & model per fitur).'];

        \App\Models\Setting::updateOrCreate(['key' => 'chatbot_tpm_limit'], ['value' => (string) max(1000, (int) $this->chatbot_tpm_limit)]);
        // 0 = tanpa batas: grup report boleh memuat semua data tanpa dipotong.
        \App\Models\Setting::updateOrCreate(['key' => 'chatbot_report_data_limit'], ['value' => (string) max(0, (int) $this->chatbot_report_data_limit)]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_wa_secondary'], ['value' => trim($this->admin_wa_secondary)]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_wa_group_id'], ['value' => \App\Models\Setting::sanitizeJid($this->admin_wa_group_id)]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_report_group_id'], ['value' => \App\Models\Setting::sanitizeJid($this->admin_report_group_id)]);
        \App\Models\Setting::updateOrCreate(['key' => 'admin_notify_group_id'], ['value' => \App\Models\Setting::sanitizeJid($this->admin_notify_group_id)]);
        \App\Models\Setting::updateOrCreate(['key' => 'chatbot_custom_knowledge'], ['value' => json_encode(array_values($this->chatbot_custom_knowledge))]);

        // Save OneSignal Settings
        \App\Models\Setting::updateOrCreate(['key' => 'onesignal_app_id'], ['value' => trim($this->onesignal_app_id)]);
        \App\Models\Setting::updateOrCreate(['key' => 'onesignal_rest_api_key'], ['value' => trim($this->onesignal_rest_api_key)]);
        \App\Models\Setting::updateOrCreate(['key' => 'onesignal_safari_web_id'], ['value' => trim($this->onesignal_safari_web_id)]);


        // Cerminkan kunci AI ke .env sebagai jaring pengaman kalau database
        // nanti tidak terbaca. Yang penting di sini DB, bukan .env.
        $this->syncGeminiKeysToEnv([
            'GEMINI_API_KEY' => $this->chatbot_api_key,
            'GEMINI_REPORT_API_KEY' => $this->report_api_key,
            'GEMINI_BROADCAST_API_KEY' => $this->broadcast_api_key,
        ]);

        session()->flash('general_message', 'Pengaturan AI & Umum berhasil disimpan.');
    }

    /**
     * Aturan validasi untuk enam field kunci/model AI per fitur.
     *
     * Dipisah dari `saveGeneralSettings` supaya tombol "Tes Kunci" bisa
     * validating & menyimpan hanya bagian AI, tanpa ikut mensyaratkan field
     * umum yang tidak ada hubungannya (mis. `home_title`).
     */
    private function validateAiFeatureSettings(): void
    {
        $rules = [
            'chatbot_api_key' => 'nullable|string|max:200',
            'report_api_key' => 'nullable|string|max:200',
            'broadcast_api_key' => 'nullable|string|max:200',
            'chatbot_model' => 'nullable|string',
            'report_model' => 'nullable|string',
            'broadcast_model' => 'nullable|string',
        ];

        for ($slot = 2; $slot <= \App\Services\GeminiAIService::KEY_POOL_SIZE; $slot++) {
            $rules[\App\Services\GeminiAIService::keySlotName('customer', $slot)] = 'nullable|string|max:200';
        }

        $messages = [
            'chatbot_api_key.max' => 'API Key Customer kelewat panjang (maksimal 200 karakter).',
            'report_api_key.max' => 'API Key Laporan kelewat panjang (maksimal 200 karakter).',
            'broadcast_api_key.max' => 'API Key Broadcast kelewat panjang (maksimal 200 karakter).',
        ];

        for ($slot = 2; $slot <= \App\Services\GeminiAIService::KEY_POOL_SIZE; $slot++) {
            $prop = \App\Services\GeminiAIService::keySlotName('customer', $slot);
            $messages["{$prop}.max"] = "API Key Customer slot {$slot} kelewat panjang (maksimal 200 karakter).";
        }

        $this->validate($rules, $messages);
    }

    /** Tulis kunci + model ketiga fitur ke tabel settings. */
    private function persistAiFeatureSettings(): void
    {
        $default = \App\Services\GeminiAIService::DEFAULT_MODEL;

        \App\Models\Setting::updateOrCreate(['key' => 'chatbot_api_key'], ['value' => trim((string) $this->chatbot_api_key)]);

        for ($slot = 2; $slot <= \App\Services\GeminiAIService::KEY_POOL_SIZE; $slot++) {
            $prop = \App\Services\GeminiAIService::keySlotName('customer', $slot);
            \App\Models\Setting::updateOrCreate(
                ['key' => \App\Services\GeminiAIService::keySlotName('customer', $slot)],
                ['value' => trim((string) $this->{$prop})]
            );
        }

        \App\Models\Setting::updateOrCreate(['key' => 'chatbot_model'], ['value' => $this->chatbot_model ?: $default]);
        \App\Models\Setting::updateOrCreate(['key' => 'report_api_key'], ['value' => trim((string) $this->report_api_key)]);
        \App\Models\Setting::updateOrCreate(['key' => 'report_model'], ['value' => $this->report_model ?: $default]);
        \App\Models\Setting::updateOrCreate(['key' => 'broadcast_api_key'], ['value' => trim((string) $this->broadcast_api_key)]);
        \App\Models\Setting::updateOrCreate(['key' => 'broadcast_model'], ['value' => $this->broadcast_model ?: $default]);
    }

    /**
     * Tulis kunci AI ke .env tanpa mengubah baris yang sama dua kali.
     *
     * Versi lama memakai `str_contains($env, "KEY=")` lalu `preg_replace("/^KEY=/m")`.
     * Dua hal salah di situ: (1) pengecekan menemukan baris yang sudah dikomentari
     * (`#GEMINI_API_KEY=...`) tapi regex-nya tidak, jadi hasilnya kunci aktif baru
     * ditambahkan di bawah dan kunci lama tetap ada — env jadi punya duplikat; dan
     * (2) nilai kosong untuk fitur yang sengaja dikosongkan ikut menimpa baris yang
     * ada, padahal "kosong" di Pengaturan berarti "pakai key customer".
     *
     * Jadi sekarang satu baris saja yang disobek: yang sudah ada ditulis ulang,
     * yang belum ada ditambahkan, dan nilai kosong berarti "kosongkan".
     */
    private function syncGeminiKeysToEnv(array $values): void
    {
        try {
            $envPath = base_path('.env');
            if (! is_writable($envPath)) {
                return;
            }

            $envContent = file_get_contents($envPath);
            if ($envContent === false) {
                return;
            }

            $appended = [];
            foreach ($values as $key => $value) {
                $value = trim((string) $value);

                if (preg_match("/^{$key}=.*$/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent, 1);
                } else {
                    $appended[] = "{$key}={$value}";
                }
            }

            if ($appended !== []) {
                $envContent = rtrim($envContent, "\n") . "\n\n# Ditulis dari menu Pengaturan (AI per fitur)\n" . implode("\n", $appended) . "\n";
            }

            file_put_contents($envPath, $envContent);
        } catch (\Throwable $e) {
            // .env hanya cadangan; kegagalan menulis tidak boleh menggagalkan simpan.
            \Illuminate\Support\Facades\Log::warning('Gagal menulis kunci AI ke .env: ' . $e->getMessage());
        }
    }

    /**
     * Uji kunci API satu fitur dengan satu panggilan sangat kecil ke modelnya.
     *
     * Kunci & model fitur itu ditulis ke database dulu supaya yang dites benar
     * yang akan dipakai — kalau tidak, tombol "Tes" selalu menguji kunci lama
     * di database padahal admin sudah mengetik yang baru, dan tampilannya berbohong.
     */
    public function testAiKey(string $feature)
    {
        if (auth()->user()->role !== 'admin') return;
        if (! in_array($feature, ['customer', 'report', 'broadcast'], true)) return;

        $this->validateAiFeatureSettings();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        $this->persistAiFeatureSettings();

        $this->testing_ai_feature = $feature;
        $result = \App\Services\GeminiAIService::testKey($feature);
        $this->testing_ai_feature = null;

        $this->ai_key_test = [$feature => $result];
        $this->ai_key_test_slot = [];
        $this->aiSettingsMessage = $result['ok']
            ? ['ok' => true, 'text' => 'Kunci & model ' . ucfirst(\App\Services\GeminiAIService::featureLabel($feature)) . ' tersimpan dan valid.']
            : ['ok' => false, 'text' => $result['message']];
    }

    /**
     * Tes satu slot kunci tertentu dari pool customer.
     *
     * Slot 2-4 tidak punya tombol Tes sendiri kalau dirangkai jadi satu, padahal
     * justru slot itulah yang paling sering mati diam-diam (project-nya kehabisan
     * kuota harian). Tes per slot supaya kelihatan sehat atau merah dari awal.
     */
    public function testAiKeySlot(string $feature, int $slot)
    {
        if (auth()->user()->role !== 'admin') return;
        if (! in_array($feature, ['customer', 'report', 'broadcast'], true)) return;

        $max = \App\Services\GeminiAIService::KEY_POOL_SIZE;
        $slot = max(1, min($max, $slot));

        $this->validateAiFeatureSettings();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        $this->persistAiFeatureSettings();

        $this->testing_ai_feature = "{$feature}_{$slot}";
        $result = \App\Services\GeminiAIService::testKeySlot($feature, $slot);
        $this->testing_ai_feature = null;

        $this->ai_key_test_slot = ["{$feature}_{$slot}" => $result];
    }

    /**
     * Susun draf pesan broadcast dari brief yang diketik admin.
     *
     * Hasilnya hanya mengisi kolom pesan — belum dikirim. Admin tetap wajib baca
     * dan ubah dulu, karena AI boleh salah menyebut stok atau promo.
     */
    public function generateBroadcastDraft()
    {
        if (auth()->user()->role !== 'admin') return;

        $this->validate([
            'broadcast_ai_brief' => 'required|string|max:1000',
        ], [
            'broadcast_ai_brief.required' => 'Tulis dulu singkatannya, mis. "promo iPhone 13 diskon weekend ini".',
        ]);
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->is_generating_broadcast = true;
        try {
            $draft = \App\Services\GeminiAIService::draftBroadcast($this->broadcast_ai_brief, $this->broadcast_ai_tone);
        } finally {
            $this->is_generating_broadcast = false;
        }

        if ($draft === null) {
            $this->aiSettingsMessage = ['ok' => false, 'text' => 'Gagal menyusun draf broadcast. Cek API Key & Model bagian Broadcast di atas.'];
            return;
        }

        $this->active_broadcast_message = $draft;
        $this->aiSettingsMessage = ['ok' => true, 'text' => 'Draf dibuat dari brief. Tinjau & ubah dulu sebelum dikirim.'];
    }

    public function updatedIsMaintenance($value)
    {
        if (auth()->user()->role !== 'admin') return;
        \App\Models\Setting::updateOrCreate(['key' => 'is_maintenance'], ['value' => $value ? '1' : '0']);
        session()->flash('general_message', 'Mode Pemeliharaan ' . ($value ? 'AKTIF' : 'NON-AKTIF'));
    }

    public function updatedMaintenanceMessage($value)
    {
        if (auth()->user()->role !== 'admin') return;
        \App\Models\Setting::updateOrCreate(['key' => 'maintenance_message'], ['value' => $value]);
    }

    public function saveGreetings()
    {
        if (auth()->user()->role !== 'admin') return;
        
        \App\Models\Setting::updateOrCreate(['key' => 'greeting_morning'], ['value' => $this->greeting_morning]);
        \App\Models\Setting::updateOrCreate(['key' => 'greeting_day'], ['value' => $this->greeting_day]);
        \App\Models\Setting::updateOrCreate(['key' => 'greeting_afternoon'], ['value' => $this->greeting_afternoon]);
        \App\Models\Setting::updateOrCreate(['key' => 'greeting_evening'], ['value' => $this->greeting_evening]);
        \App\Models\Setting::updateOrCreate(['key' => 'greeting_night'], ['value' => $this->greeting_night]);
        \App\Models\Setting::updateOrCreate(['key' => 'is_greeting_active'], ['value' => $this->is_greeting_active ? '1' : '0']);

        session()->flash('greeting_message', 'Sapaan Beranda berhasil diperbarui!');
    }

    public function addKnowledge()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'new_knowledge_key' => 'required',
            'new_knowledge_value' => 'required',
        ], [
            'new_knowledge_key.required' => 'Topik/Kata kunci wajib diisi.',
            'new_knowledge_value.required' => 'Jawaban/Aturan wajib diisi.',
        ]);

        $this->chatbot_custom_knowledge[] = [
            'key' => trim($this->new_knowledge_key),
            'value' => trim($this->new_knowledge_value),
            'created_at' => now()->toDateTimeString(),
        ];

        \App\Models\Setting::updateOrCreate(
            ['key' => 'chatbot_custom_knowledge'],
            ['value' => json_encode(array_values($this->chatbot_custom_knowledge))]
        );

        $this->reset(['new_knowledge_key', 'new_knowledge_value']);
        session()->flash('general_message', 'Memori pengetahuan AI berhasil ditambahkan.');
    }

    public function removeKnowledge($index)
    {
        if (auth()->user()->role !== 'admin') return;
        unset($this->chatbot_custom_knowledge[$index]);
        $this->chatbot_custom_knowledge = array_values($this->chatbot_custom_knowledge);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'chatbot_custom_knowledge'],
            ['value' => json_encode($this->chatbot_custom_knowledge)]
        );

        session()->flash('general_message', 'Memori pengetahuan AI berhasil dihapus.');
    }

    // WhatsApp Broadcast Group & Shortcut Methods
    public function editBroadcastGroup($index)
    {
        if (auth()->user()->role !== 'admin') return;
        if (!isset($this->broadcast_groups[$index])) return;

        $grp = $this->broadcast_groups[$index];
        $this->editing_group_index = $index;
        $this->editing_group_id = $grp['id'] ?? null;
        $this->new_group_name = $grp['name'] ?? '';
        $this->new_group_numbers = implode("\n", $grp['numbers'] ?? []);
        $this->selected_customer_tags = [];
    }

    public function cancelEditBroadcastGroup()
    {
        $this->reset(['editing_group_index', 'editing_group_id', 'new_group_name', 'new_group_numbers', 'selected_customer_tags', 'customer_search_query']);
    }

    public function tagCustomerToGroup(string $phone, string $name)
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) return;

        // Cek jika nomor sudah ada di input
        $existing = preg_split('/[\r\n,]+/', $this->new_group_numbers);
        $existing = array_map(fn($n) => preg_replace('/[^0-9]/', '', trim($n)), $existing);
        $existing = array_filter($existing);

        if (!in_array($clean, $existing)) {
            $existing[] = $clean;
            $this->new_group_numbers = implode("\n", $existing);
        }

        // Tambah ke selected_customer_tags untuk visual badge jika belum ada
        $alreadyTagged = false;
        foreach ($this->selected_customer_tags as $tag) {
            if ($tag['phone'] === $clean) {
                $alreadyTagged = true;
                break;
            }
        }
        if (!$alreadyTagged) {
            $this->selected_customer_tags[] = [
                'phone' => $clean,
                'name' => $name ?: 'Pelanggan',
            ];
        }

        $this->customer_search_query = '';
    }

    public function removeCustomerTag(string $phone)
    {
        $this->selected_customer_tags = array_values(array_filter($this->selected_customer_tags, fn($t) => $t['phone'] !== $phone));
        
        $existing = preg_split('/[\r\n,]+/', $this->new_group_numbers);
        $existing = array_map(fn($n) => preg_replace('/[^0-9]/', '', trim($n)), $existing);
        $existing = array_filter($existing, fn($n) => $n !== $phone && !empty($n));
        $this->new_group_numbers = implode("\n", $existing);
    }

    public function saveBroadcastGroup()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'new_group_name' => 'required',
            'new_group_numbers' => 'required',
        ], [
            'new_group_name.required' => 'Nama group pelanggan wajib diisi.',
            'new_group_numbers.required' => 'Nomor WhatsApp wajib diisi (pisahkan koma atau baris baru).',
        ]);

        // Parse numbers
        $rawLines = preg_split('/[\r\n,]+/', $this->new_group_numbers);
        $cleanNumbers = [];
        foreach ($rawLines as $num) {
            $c = preg_replace('/[^0-9]/', '', trim($num));
            if (!empty($c) && strlen($c) >= 9) {
                $cleanNumbers[] = $c;
            }
        }
        $cleanNumbers = array_values(array_unique($cleanNumbers));

        if ($this->editing_group_index !== null && isset($this->broadcast_groups[$this->editing_group_index])) {
            // Mode Update
            $this->broadcast_groups[$this->editing_group_index]['name'] = trim($this->new_group_name);
            $this->broadcast_groups[$this->editing_group_index]['numbers'] = $cleanNumbers;
            $this->broadcast_groups[$this->editing_group_index]['count'] = count($cleanNumbers);
            $this->broadcast_groups[$this->editing_group_index]['updated_at'] = now()->toDateTimeString();
            $actionMsg = 'Grup broadcast WhatsApp berhasil diperbarui.';
        } else {
            // Mode Tambah Baru
            $this->broadcast_groups[] = [
                'id' => uniqid('grp_'),
                'name' => trim($this->new_group_name),
                'numbers' => $cleanNumbers,
                'count' => count($cleanNumbers),
                'created_at' => now()->toDateTimeString(),
            ];
            $actionMsg = 'Grup broadcast WhatsApp berhasil dibuat.';
        }

        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_broadcast_groups'],
            ['value' => json_encode(array_values($this->broadcast_groups))]
        );

        $this->cancelEditBroadcastGroup();
        session()->flash('general_message', $actionMsg);
    }

    public function addBroadcastGroup()
    {
        $this->saveBroadcastGroup();
    }

    public function importCustomersToGroup()
    {
        if (auth()->user()->role !== 'admin') return;

        $query = \App\Models\Rental::whereNotNull('no_wa')->where('no_wa', '!=', '');

        $filterLabel = 'Semua Pelanggan';

        if (!empty($this->import_filter_unit_id)) {
            $unit = \App\Models\Unit::find($this->import_filter_unit_id);
            if ($unit) {
                $query->whereHas('units', function ($q) use ($unit) {
                    $q->where('units.id', $unit->id);
                });
                $filterLabel = 'Pelanggan ' . ($unit->nama_lengkap ?: $unit->seri);
            }
        }

        if (!empty($this->import_filter_status)) {
            $query->where('status', $this->import_filter_status);
            $filterLabel .= ' (' . ucfirst($this->import_filter_status) . ')';
        }

        $numbers = $query->pluck('no_wa')
            ->map(fn($n) => preg_replace('/[^0-9]/', '', $n))
            ->filter(fn($n) => strlen($n) >= 9)
            ->unique()
            ->values()
            ->toArray();

        if (empty($numbers)) {
            session()->flash('broadcast_error', 'Tidak ada data kontak nomor WhatsApp yang cocok dengan filter yang dipilih.');
            return;
        }

        $this->broadcast_groups[] = [
            'id' => uniqid('grp_'),
            'name' => $filterLabel . ' (' . count($numbers) . ' kontak)',
            'numbers' => $numbers,
            'count' => count($numbers),
            'created_at' => now()->toDateTimeString(),
        ];

        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_broadcast_groups'],
            ['value' => json_encode(array_values($this->broadcast_groups))]
        );

        session()->flash('general_message', "Berhasil membuat grup \"{$filterLabel}\" dengan " . count($numbers) . " kontak dari database.");
    }

    public function removeBroadcastGroup($index)
    {
        if (auth()->user()->role !== 'admin') return;
        unset($this->broadcast_groups[$index]);
        $this->broadcast_groups = array_values($this->broadcast_groups);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_broadcast_groups'],
            ['value' => json_encode($this->broadcast_groups)]
        );

        session()->flash('general_message', 'Grup broadcast berhasil dihapus.');
    }

    public function addBroadcastShortcut()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'new_shortcut_code' => 'required|alpha_dash',
            'new_shortcut_title' => 'required',
            'new_shortcut_message' => 'required',
        ]);

        $this->broadcast_shortcuts[] = [
            'code' => trim($this->new_shortcut_code),
            'title' => trim($this->new_shortcut_title),
            'message' => trim($this->new_shortcut_message),
        ];

        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_broadcast_shortcuts'],
            ['value' => json_encode(array_values($this->broadcast_shortcuts))]
        );

        $this->reset(['new_shortcut_code', 'new_shortcut_title', 'new_shortcut_message']);
        session()->flash('general_message', 'Shortcut pesan broadcast berhasil ditambahkan.');
    }

    public function removeBroadcastShortcut($index)
    {
        if (auth()->user()->role !== 'admin') return;
        unset($this->broadcast_shortcuts[$index]);
        $this->broadcast_shortcuts = array_values($this->broadcast_shortcuts);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_broadcast_shortcuts'],
            ['value' => json_encode($this->broadcast_shortcuts)]
        );

        session()->flash('general_message', 'Shortcut pesan broadcast dihapus.');
    }

    public function applyShortcutToMessage($code)
    {
        foreach ($this->broadcast_shortcuts as $sc) {
            if ($sc['code'] === $code) {
                $this->active_broadcast_message = $sc['message'];
                break;
            }
        }
    }

    public function sendBroadcast()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'active_broadcast_group' => 'required',
            'active_broadcast_message' => 'required',
        ], [
            'active_broadcast_group.required' => 'Pilih grup tujuan broadcast.',
            'active_broadcast_message.required' => 'Isi pesan broadcast tidak boleh kosong.',
        ]);

        // Temukan nomor-nomor di group
        $selectedNumbers = [];
        $groupName = '';
        foreach ($this->broadcast_groups as $grp) {
            if ($grp['id'] === $this->active_broadcast_group) {
                $selectedNumbers = $grp['numbers'] ?? [];
                $groupName = $grp['name'] ?? 'Grup';
                break;
            }
        }

        if (empty($selectedNumbers)) {
            session()->flash('broadcast_error', 'Grup yang dipilih tidak memiliki kontak nomor WhatsApp.');
            return;
        }

        $waService = app(\App\Services\WhatsAppService::class);
        $successCount = 0;
        $failCount = 0;
        $total = count($selectedNumbers);

        // Variasi salam anti-spam identik
        $greetings = ['Halo Kak! 😊', 'Halo Kak,', 'Hai Kak! ✨', 'Halo Kak, salam dari Rent Space!'];

        foreach ($selectedNumbers as $i => $phone) {
            $msgToSend = $this->active_broadcast_message;
            if (str_starts_with($msgToSend, 'Halo Kak')) {
                $greeting = $greetings[array_rand($greetings)];
                $msgToSend = preg_replace('/^Halo Kak(!|,\s*|\s*)/', $greeting . ' ', $msgToSend);
            }

            $res = $waService->sendMessage($phone, $msgToSend);
            if ($res) {
                $successCount++;
            } else {
                $failCount++;
            }

            // ANTI-BANNED PROTECTION:
            // Jeda dinamis acak menyerupai manusia agar tidak terdeteksi bot spam oleh WhatsApp
            if ($this->broadcast_delay_mode === 'safe') {
                $sleepUs = rand(2000000, 4000000); // 2.0 s/d 4.0 detik per pesan
                usleep($sleepUs);

                // Istirahat ekstra 5 detik setiap kelipatan 10 pesan
                if (($i + 1) % 10 === 0 && ($i + 1) < $total) {
                    sleep(5);
                }
            } else {
                // Normal mode
                $sleepUs = rand(1000000, 2000000); // 1.0 s/d 2.0 detik
                usleep($sleepUs);
            }
        }

        session()->flash('general_message', "✅ Broadcast ke {$groupName} selesai: {$successCount} sukses, {$failCount} gagal.");
        $this->reset(['active_broadcast_message', 'active_broadcast_group']);
    }

    public function addFaq()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->about_faq_items[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq($index)
    {
        if (auth()->user()->role !== 'admin') return;
        unset($this->about_faq_items[$index]);
        $this->about_faq_items = array_values($this->about_faq_items);
    }

    public function saveFaqSettings()
    {
        if (auth()->user()->role !== 'admin')
            return;

        \App\Models\Setting::updateOrCreate(
            ['key' => 'about_faq_items'],
            ['value' => json_encode($this->about_faq_items)]
        );

        session()->flash('faq_message', 'Konten Halaman Tentang (FAQ) berhasil disimpan.');
    }

    public function saveHero($slot = 1)
    {
        if (auth()->user()->role !== 'admin')
            return;

        $photoProp = $slot == 1 ? 'hero_photo' : ($slot == 2 ? 'hero2_photo' : 'hero3_photo');
        $keyName = $slot == 1 ? 'hero' : ($slot == 2 ? 'hero2' : 'hero3');

        $this->validate([
            $photoProp => 'required|image|max:6144|mimes:jpg,jpeg,png,webp',
        ]);

        $filename = 'hero' . $slot . '_' . time() . '.' . $this->$photoProp->getClientOriginalExtension();

        // Menggunakan DOCUMENT_ROOT untuk langsung mengarah ke 'htdocs' di InfinityFree
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads';

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $destination = $uploadPath . '/' . $filename;
        file_put_contents($destination, file_get_contents($this->$photoProp->getRealPath()));

        \App\Models\Setting::updateOrCreate(
            ['key' => $keyName],
            ['value' => $filename]
        );

        $this->$keyName = $filename;
        $this->reset($photoProp);

        session()->flash('hero_message', "Hero foto slot $slot berhasil diperbarui!");
    }

    public function saveQris()
    {
        if (auth()->user()->role !== 'admin') return;
        $this->validate([
            'qris_photo' => 'required|image|max:2048', // 2MB Max
        ]);

        $filename = 'qris_' . time() . '.' . $this->qris_photo->getClientOriginalExtension();

        // Menggunakan DOCUMENT_ROOT untuk langsung mengarah ke 'htdocs' di InfinityFree
        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads';

        // Buat folder jika belum ada
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Pindahkan file foto dari direktori temporary Livewire ke htdocs/uploads
        $destination = $uploadPath . '/' . $filename;
        file_put_contents($destination, file_get_contents($this->qris_photo->getRealPath()));

        \App\Models\Setting::updateOrCreate(
            ['key' => 'qris'],
            ['value' => $filename]
        );

        $this->qris = $filename;

        session()->flash('message', 'QRIS Photo successfully updated.');
    }
    public function exportData()
    {
        if (auth()->user()->role !== 'admin')
            return;

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
        if (auth()->user()->role !== 'admin')
            return;

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


    public function resendMail($logId)
    {
        if (auth()->user()->role !== 'admin') return;

        $log = \App\Models\MailLog::findOrFail($logId);
        $rental = $log->rental;
        
        if (!$rental) {
            session()->flash('email_error', 'Data rental tidak ditemukan untuk kirim ulang.');
            return;
        }

        try {
            $mailable = null;
            $type = $log->type;

            // Map type to mailable class if it's just a label
            if (str_contains($type, 'Admin Notification') || str_contains($type, 'Customer Receipt') || str_contains($type, 'NewOrderNotification')) {
                $mailable = new \App\Mail\NewOrderNotification($rental);
            } elseif (str_contains($type, 'Payment Confirmation') || str_contains($type, 'PaymentConfirmedNotification')) {
                $mailable = new \App\Mail\PaymentConfirmedNotification($rental);
            } elseif (str_contains($type, 'Order Cancellation') || str_contains($type, 'OrderCancelledNotification')) {
                $mailable = new \App\Mail\OrderCancelledNotification($rental);
            } else {
                // If it's a class name
                if (class_exists($type)) {
                    $mailable = new $type($rental);
                }
            }

            if ($mailable) {
                $recipients = array_map('trim', explode(',', $log->recipient));
                \App\Helpers\MailHelper::logAndQueue($recipients, $mailable, $log->type, $log->id);
                session()->flash('email_message', 'Email berhasil dikirim ulang ke ' . $log->recipient);
            } else {
                session()->flash('email_error', 'Format email tidak dikenali.');
            }
        } catch (\Exception $e) {
            session()->flash('email_error', 'Gagal kirim ulang: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $usersQuery = \App\Models\User::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection);
        
        $adminMailLogs = \App\Models\MailLog::with('rental')
            ->where('type', 'like', '%Admin%')
            ->orderBy('sent_at', 'desc')
            ->paginate(10, ['*'], 'adminMailPage');

        $customerMailLogs = \App\Models\MailLog::with('rental')
            ->where('type', 'not like', '%Admin%')
            ->orderBy('sent_at', 'desc')
            ->paginate(10, ['*'], 'customerMailPage');

        $unitsList = \App\Models\Unit::orderBy('seri')->get();

        // Cari data pelanggan untuk ditag 1 per 1 ke grup
        $searchCustomers = [];
        if (!empty(trim($this->customer_search_query))) {
            $q = trim($this->customer_search_query);
            $searchCustomers = \App\Models\Rental::whereNotNull('no_wa')
                ->where('no_wa', '!=', '')
                ->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('no_wa', 'like', "%{$q}%");
                })
                ->select('nama', 'no_wa')
                ->distinct()
                ->limit(8)
                ->get()
                ->map(fn($r) => [
                    'nama' => $r->nama,
                    'phone' => preg_replace('/[^0-9]/', '', $r->no_wa),
                ])
                ->unique('phone')
                ->values()
                ->toArray();
        }

        return view('livewire.admin.settings', [
            'users' => $usersQuery->paginate($this->perPage, ['*'], 'userPage'),
            'adminMailLogs' => $adminMailLogs,
            'customerMailLogs' => $customerMailLogs,
            'unitsList' => $unitsList,
            'searchCustomers' => $searchCustomers,
        ])->layout('layouts.admin');
    }
}
