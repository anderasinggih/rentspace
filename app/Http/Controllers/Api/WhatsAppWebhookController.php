<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Setting;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedKey = config('services.whatsapp.api_key', 'rentspace_secret_wa_token_2026');

        if ($apiKey !== $expectedKey) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $phone = $request->input('phone');
        $name = $request->input('name', 'Kak');
        $text = trim((string) $request->input('text', ''));
        $action = $request->input('action');

        // Jika ada request forward ke admin lain (Customer butuh bantuan admin)
        if ($action === 'forward_admin') {
            $this->notifySecondaryAdmin(
                $name,
                $phone,
                $text,
                (string) $request->input('reason', 'minta dibantu admin')
            );
            return response()->json(['status' => true, 'message' => 'Admin notified']);
        }

        // Jika request dari grup report internal (bot di-tag di grup report)
        if ($action === 'report_group_query') {
            $senderJid = $request->input('sender_jid', '');
            $reportGroupId = Setting::sanitizeJid(Setting::getVal('admin_report_group_id', ''));
            $senderJid = Setting::sanitizeJid($senderJid);

            if ($reportGroupId === '' || $senderJid !== $reportGroupId) {
                Log::warning('report_group_query ditolak: ID grup tidak cocok', [
                    'grup_masuk' => $senderJid,
                    'grup_terdaftar' => $reportGroupId,
                ]);
                return response()->json(['status' => true, 'reply' => null]);
            }

            // Perintah memori:/tag langsung (tanpa perlu mention bot)
            $memoriReply = $this->handleMemoryCommand($text, $name, $reportGroupId);
            if ($memoriReply !== null) {
                return response()->json(['status' => true, 'reply' => $memoriReply]);
            }

            $aiReply = \App\Services\GeminiAIService::replyInternal($text, $name);
            return response()->json(['status' => true, 'reply' => $aiReply ?: null]);
        }


        // 0. Cek Perintah Khusus Admin: /rentspacesettings & /broadcast
        if (str_starts_with(strtolower($text), '/rentspacesettings') || str_starts_with(strtolower($text), '/broadcast')) {
            $senderJid = Setting::sanitizeJid($request->input('sender_jid', ''));
            $authorizedGroupId = Setting::sanitizeJid(Setting::getVal('admin_wa_group_id', ''));

            // Jika perintah berasal dari grup WhatsApp (@g.us)
            if (str_ends_with($senderJid, '@g.us')) {
                if (empty($authorizedGroupId)) {
                    return response()->json([
                        'status' => true,
                        'reply' => "⚠️ *Akses Ditolak!*\nID Grup WhatsApp Admin belum didaftarkan di System Settings Web Admin.\n\nKetik `!getid` di grup ini, lalu salin ID-nya dan tempelkan pada menu:\n*Web Admin -> Settings -> Tab WhatsApp -> ID Grup WhatsApp Admin*."
                    ]);
                }

                if ($senderJid !== $authorizedGroupId) {
                    return response()->json([
                        'status' => true,
                        'reply' => "⛔ *Akses Ditolak!*\nGrup ini tidak terdaftar sebagai grup WhatsApp resmi admin Rent Space.\nPerintah admin dan broadcast diblokir demi keamanan data pelanggan."
                    ]);
                }
            }

            if (str_starts_with(strtolower($text), '/rentspacesettings')) {
                $adminReply = $this->handleRentSpaceSettingsCommand($text, $phone);
                return response()->json(['status' => true, 'reply' => $adminReply]);
            }

            if (str_starts_with(strtolower($text), '/broadcast')) {
                $broadcastReply = $this->handleBroadcastAdminCommand($text, $phone);
                return response()->json(['status' => true, 'reply' => $broadcastReply]);
            }
        }

        // 1. Cek perintah KATALOG / LIST / DAFTAR HARGA
        if (preg_match('/^(katalog|pricelist|harga|list|daftar\s*harga|1)$/i', $text)) {
            $reply = $this->buildCatalogResponse();
            return response()->json(['status' => true, 'reply' => $reply]);
        }

        // 2. Cek status booking (Format: "CEK RS-XXXX" atau "CEK [KODE]" atau ketik angka 2)
        // "cek" hanya dibaca sebagai permintaan status bila kata sisanya memang
        // terlihat seperti kode booking. Tanpa guard ini, pertanyaan natural
        // seperti "cek unit iPhone XR ready?" ikut dicocokkan sebagai kode
        // pesanan dan customer menerima jawaban yang salah.
        $bareStatusWord = preg_match('/^(cek|status|order)$/i', $text) || $text === '2';
        $codeQuery = '';
        if (preg_match('/^(cek|status|order)\s+(.+)$/i', $text, $codeMatches)) {
            $candidate = trim($codeMatches[2]);
            // Kode booking di sistem: 10 karakter alfanumerik, boleh diawali "RS".
            if (preg_match('/^(?:RS[- ]?)?[A-Z0-9]{4,20}$/i', $candidate)) {
                $codeQuery = $candidate;
            }
        }

        if ($bareStatusWord || $codeQuery !== '') {

            // Jika user hanya ketik "CEK" atau "2", coba cari rental terakhir berdasarkan nomor WA customer
            if (empty($codeQuery)) {
                $formattedPhone = $this->normalizePhone($phone);
                $rental = Rental::with('units')
                    ->where(function ($q) use ($phone, $formattedPhone) {
                        $q->where('no_wa', $phone)
                          ->orWhere('no_wa', $formattedPhone)
                          ->orWhere('no_wa', 'like', '%' . substr($formattedPhone, -9));
                    })
                    ->latest()
                    ->first();

                if ($rental) {
                    $reply = $this->formatRentalStatusResponse($rental, $name);
                    return response()->json(['status' => true, 'reply' => $reply]);
                }

                $reply = "Halo Kak *{$name}*, untuk mengecek status pesanan, silakan ketik:\n" .
                    "*CEK [KODE_BOOKING]*\n\n" .
                    "Contoh: *CEK RS123456*\n\n" .
                    "Kode booking bisa dilihat pada email invoice atau bukti pemesanan Kakak ya! 😊";
                return response()->json(['status' => true, 'reply' => $reply]);
            }

            // Jika mencantumkan kode booking
            $rental = Rental::with('units')
                ->where('booking_code', 'like', '%' . $codeQuery . '%')
                ->first();

            if (!$rental) {
                $reply = "Maaf Kak *{$name}*, pesanan dengan kode *{$codeQuery}* tidak ditemukan.\n\n" .
                    "Mohon pastikan kode booking sudah benar ya Kak! 🙏";
                return response()->json(['status' => true, 'reply' => $reply]);
            }

            $reply = $this->formatRentalStatusResponse($rental, $name);
            return response()->json(['status' => true, 'reply' => $reply]);
        }

        // Default: Jika ada pertanyaan umum dari customer, gunakan AI (Gemini Flash) jika diaktifkan
        $isAiActive = \App\Models\Setting::getVal('is_chatbot_active', '1') == '1';
        if ($isAiActive && !empty($text)) {
            $senderJid = $request->input('sender_jid', $phone);

            try {
                $result = \App\Services\GeminiAIService::customerReply($text, $name, $senderJid, $phone);
            } catch (\Throwable $e) {
                // AI error tak terduga: jangan biarkan customer jatuh ke balasan
                // generik "cek in dulu" — teruskan ke admin seperti handoff.
                Log::warning('GeminiAIService::customerReply melempar exception, diteruskan ke admin.', [
                    'nama' => $name,
                    'no_wa' => $phone,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => true,
                    'reply' => null,
                    'handoff' => true,
                    'handoff_reason' => 'AI sedang gangguan',
                ]);
            }

            // Chat di luar topik sewa: tidak dijawab AI, cuma diarahkan ke admin.
            // Bot tetap sempat membalas (supaya customer tidak merasa dibohongi),
            // lalu meneruskan pesan ini ke admin.
            if ($result['handoff']) {
                Log::info('Chat customer di luar topik sewa, diteruskan ke admin.', [
                    'nama' => $name,
                    'no_wa' => $phone,
                    'alasan' => $result['reason'],
                ]);

                return response()->json([
                    'status' => true,
                    'reply' => null,
                    'handoff' => true,
                    'handoff_reason' => $result['reason'],
                ]);
            }

            if (!empty($result['reply'])) {
                return response()->json(['status' => true, 'reply' => $result['reply']]);
            }

            // AI aktif tapi tidak menghasilkan jawaban (API key kosong, kuota
            // habis, jaringan bermasalah). Sebelumnya dijawab null lalu bot milih
            // balasan generik "cek in dulu" yang tidak menolong siapa-siapa dan
            // admin tidak pernah diberi tahu. Sekarang diteruskan ke admin.
            Log::warning('Chat customer tidak terjawab AI, diteruskan ke admin.', [
                'nama' => $name,
                'no_wa' => $phone,
                'pesan' => mb_substr($text, 0, 200),
            ]);

            return response()->json([
                'status' => true,
                'reply' => null,
                'handoff' => true,
                'handoff_reason' => 'AI sedang gangguan',
            ]);
        }

        // AI nonaktif: jawab jujur dan arahkan ke admin. Kalau hanya dibalas
        // null, bot memilih kalimat generik "aku cekin dulu" yang tidak
        //-menolong dan admin tidak pernah diberi tahu.
        Log::warning('Chat customer masuk saat AI nonaktif, diteruskan ke admin.', [
            'nama' => $name,
            'no_wa' => $phone,
            'pesan' => mb_substr($text, 0, 200),
        ]);

        return response()->json([
            'status' => true,
            'reply' => null,
            'handoff' => true,
            'handoff_reason' => 'asisten AI sedang tidak aktif',
        ]);
    }

    private function buildCatalogResponse(): string
    {
        $units = Unit::where('is_active', true)->with('category')->get();

        if ($units->isEmpty()) {
            return "Halo Kak! Saat ini belum ada unit yang tersedia di katalog.\nSilakan cek website kami di https://rentspacepurwokerto.my.id 🙏";
        }

        $grouped = $units->groupBy(function ($u) {
            return $u->category ? $u->category->name : 'Unit Lainnya';
        });

        $res = "KATALOG & DAFTAR HARGA RENT SPACE\n";
        $res .= "------------------------------------\n\n";

        foreach ($grouped as $catName => $items) {
            $res .= "📂 *" . strtoupper($catName) . "*\n";
            foreach ($items as $item) {
                $p24 = $item->harga_per_hari ? 'Rp ' . number_format($item->harga_per_hari, 0, ',', '.') . '/24 jam' : null;
                $p12 = $item->harga_per_jam ? 'Rp ' . number_format($item->harga_per_jam * 12, 0, ',', '.') . '/12 jam' : null;

                $priceParts = array_filter([$p12, $p24]);
                $priceStr = !empty($priceParts) ? implode(' | ', $priceParts) : 'Hubungi Admin';

                $res .= "• *" . $item->nama_lengkap . "*\n";
                $res .= "  Tarif: " . $priceStr . "\n";
            }
            $res .= "\n";
        }

        $res .= "------------------------------------\n";
        $res .= "Mau sewa? Booking langsung di website:\n";
        $res .= "👉 https://rentspacepurwokerto.my.id/booking\n\n";
        $res .= "Ketik *MENU* untuk melihat menu lainnya.";

        return $res;
    }

    private function formatRentalStatusResponse(Rental $rental, string $name): string
    {
        $statusLabels = [
            'pending' => 'Menunggu Pembayaran',
            'pending_confirmation' => 'Menunggu Konfirmasi Admin',
            'paid' => 'Pembayaran Lunas & Terverifikasi',
            'renting' => 'Sedang Berlangsung (Disewa)',
            'completed' => 'Sewa Selesai',
            'cancelled' => 'Dibatalkan',
        ];

        $statusStr = $statusLabels[$rental->status] ?? ucfirst($rental->status);
        $rental->loadMissing('units');
        $unitNames = $rental->units->map(fn($u) => $u->nama_lengkap ?: $u->seri)->filter()->implode(', ');
        if (empty($unitNames)) {
            $unitNames = 'Unit Rental';
        }

        $start = $rental->waktu_mulai ? Carbon::parse($rental->waktu_mulai)->translatedFormat('d M Y H:i') : '-';
        $end = $rental->waktu_selesai ? Carbon::parse($rental->waktu_selesai)->translatedFormat('d M Y H:i') : '-';
        $total = 'Rp ' . number_format($rental->grand_total ?: $rental->subtotal_harga, 0, ',', '.');

        $res = "DETAIL STATUS PESANAN\n";
        $res .= "------------------------------------\n";
        $res .= "• Kode Booking: *" . $rental->booking_code . "*\n";
        $res .= "• Nama: " . $rental->nama . "\n";
        $res .= "• Unit: *" . $unitNames . "*\n";
        $res .= "• Jadwal: " . $start . " s/d " . $end . "\n";
        $res .= "• Total: *" . $total . "*\n";
        $res .= "• Status: *" . $statusStr . "*\n";
        $res .= "------------------------------------\n\n";

        if ($rental->status === 'pending') {
            $res .= "Lanjutkan pembayaran di:\n👉 https://rentspacepurwokerto.my.id/payment/" . $rental->booking_code . "\n\n";
        }

        $res .= "Terima kasih sudah memilih Rent Space Purwokerto! 🙏";

        return $res;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '62')) {
            return '0' . substr($clean, 2);
        }
        return $clean;
    }

    /**
     * Handler untuk /rentspacesettings (Kelola memori/pengetahuan AI via chat WhatsApp)
     */
    private function handleRentSpaceSettingsCommand(string $text, ?string $phone): string
    {
        $raw = trim(substr($text, strlen('/rentspacesettings')));
        $memories = json_decode(\App\Models\Setting::getVal('chatbot_custom_knowledge', '[]'), true) ?: [];

        // 1. Tampilkan Menu Bantuan jika tanpa argumen
        if (empty($raw) || strtolower($raw) === 'help' || strtolower($raw) === 'list') {
            $msg = "🛠️ *RENT SPACE AI SETTINGS* 🛠️\n";
            $msg .= "------------------------------------\n";
            $msg .= "Kelola memori & aturan respon AI langsung dari WA.\n\n";
            $msg .= "📋 *Perintah yang Tersedia:*\n";
            $msg .= "1️⃣ `/rentspacesettings list`\n   Lihat daftar semua memori AI saat ini.\n";
            $msg .= "2️⃣ `/rentspacesettings add [Kunci] = [Nilai/Aturan]`\n   Tambah memori baru.\n   _Contoh:_ `/rentspacesettings add Lokasi = https://maps.app... (Pasar Pereng)`\n   _Contoh:_ `/rentspacesettings add Jam Buka = Pengambilan unit dilayani jam 08:00 - 22:00, di atas jam 10 malam store close`\n";
            $msg .= "3️⃣ `/rentspacesettings del [Nomor]`\n   Hapus memori sesuai nomor urut.\n   _Contoh:_ `/rentspacesettings del 1`\n";
            $msg .= "4️⃣ `/rentspacesettings clear`\n   Hapus semua memori tambahan AI.\n\n";

            if (empty($memories)) {
                $msg .= "ℹ️ _Saat ini belum ada memori khusus yang tersimpan._";
            } else {
                $msg .= "📝 *Daftar Memori Saat Ini (" . count($memories) . "):*\n";
                foreach ($memories as $idx => $m) {
                    $no = $idx + 1;
                    $k = $m['key'] ?? 'Aturan';
                    $v = $m['value'] ?? '';
                    $msg .= "{$no}. *{$k}*: {$v}\n";
                }
            }

            return $msg;
        }

        // 2. Tambah Memori: /rentspacesettings add [Kunci] = [Nilai]
        if (preg_match('/^add\s+(.+)$/i', $raw, $matches)) {
            $content = trim($matches[1]);
            $key = 'Aturan Tambahan';
            $val = $content;

            if (str_contains($content, '=')) {
                $parts = explode('=', $content, 2);
                $key = trim($parts[0]);
                $val = trim($parts[1]);
            }

            if (empty($val)) {
                return "⚠️ Format salah Kak. Gunakan:\n`/rentspacesettings add Kunci = Nilai / Aturan`";
            }

            $memories[] = [
                'key' => $key,
                'value' => $val,
                'created_at' => now()->toDateTimeString(),
            ];

            \App\Models\Setting::updateOrCreate(
                ['key' => 'chatbot_custom_knowledge'],
                ['value' => json_encode($memories)]
            );

            return "✅ *Memori AI Berhasil Ditambahkan!*\n\n" .
                   "• *Kunci*: {$key}\n" .
                   "• *Isi/Aturan*: {$val}\n\n" .
                   "_AI akan otomatis menggunakan aturan ini saat menjawab pertanyaan customer._";
        }

        // 3. Hapus Memori: /rentspacesettings del [Nomor]
        if (preg_match('/^(del|delete|hapus)\s+(\d+)$/i', $raw, $matches)) {
            $targetIndex = (int) $matches[2] - 1;

            if (!isset($memories[$targetIndex])) {
                return "⚠️ Memori nomor *{$matches[2]}* tidak ditemukan.\nKetik `/rentspacesettings list` untuk melihat daftar nomor.";
            }

            $removed = $memories[$targetIndex];
            array_splice($memories, $targetIndex, 1);

            \App\Models\Setting::updateOrCreate(
                ['key' => 'chatbot_custom_knowledge'],
                ['value' => json_encode($memories)]
            );

            $delKey = $removed['key'] ?? 'Aturan';
            return "🗑️ *Memori Berhasil Dihapus!*\nMemori nomor *{$matches[2]}* ({$delKey}) telah dihapus dari database.";
        }

        // 4. Kosongkan Memori: /rentspacesettings clear
        if (strtolower($raw) === 'clear') {
            \App\Models\Setting::updateOrCreate(
                ['key' => 'chatbot_custom_knowledge'],
                ['value' => json_encode([])]
            );
            return "🧹 *Semua Memori Khusus AI Berhasil Dikosongkan!*";
        }

        return "⚠️ Perintah tidak dikenali. Ketik `/rentspacesettings help` untuk bantuan.";
    }

    /**
     * Handler untuk /broadcast (Kelola dan Eksekusi Broadcast via WA Admin / Grup Admin)
     */
    private function handleBroadcastAdminCommand(string $text, ?string $phone): string
    {
        $raw = trim(substr($text, strlen('/broadcast')));
        $groups = json_decode(\App\Models\Setting::getVal('wa_broadcast_groups', '[]'), true) ?: [];
        $shortcuts = json_decode(\App\Models\Setting::getVal('wa_broadcast_shortcuts', '[]'), true) ?: [];

        // 1. Menu Bantuan
        if (empty($raw) || strtolower($raw) === 'help') {
            $msg = "📢 *RENT SPACE BROADCAST COMMANDS* 📢\n";
            $msg .= "------------------------------------\n";
            $msg .= "Kelola dan kirim broadcast langsung dari chat/grup admin.\n\n";
            $msg .= "📋 *Pilihan Perintah:*\n";
            $msg .= "1️⃣ `/broadcast groups`\n   Lihat daftar user grup penerima broadcast.\n";
            $msg .= "2️⃣ `/broadcast addgroup [Nama Grup] = [No1, No2, ...]`\n   Tambah grup penerima baru langsung dari WA.\n   _Contoh:_ `/broadcast addgroup Pelanggan VIP = 0812345678, 0898765432`\n";
            $msg .= "3️⃣ `/broadcast delgroup [Nomor_Grup]`\n   Hapus grup penerima sesuai nomor urut.\n   _Contoh:_ `/broadcast delgroup 2`\n";
            $msg .= "4️⃣ `/broadcast sync`\n   Tarik/sinkronisasi semua nomor customer dari database ke grup 'Semua Pelanggan'.\n";
            $msg .= "5️⃣ `/broadcast shortcuts`\n   Lihat daftar template pesan broadcast.\n";
            $msg .= "6️⃣ `/broadcast send [Nomor_Grup] [Pesan]`\n   Kirim pesan broadcast teks ke grup.\n   _Contoh:_ `/broadcast send 1 Halo Kak, unit iPhone ready nih! https://rentspacepurwokerto.my.id/booking`\n";
            $msg .= "7️⃣ `/broadcast send [Nomor_Grup] /[Kode_Shortcut]`\n   Kirim broadcast menggunakan template shortcut.\n   _Contoh:_ `/broadcast send 1 /promo_weekend`\n";
            $msg .= "8️⃣ *Balas Foto + Ketik:* `/broadcast send [Nomor_Grup] from reply`\n   Kirim broadcast *foto berserta caption* dari pesan yang di-reply ke grup penerima.\n\n";
            $msg .= "🛡️ _Sistem dilengkapi proteksi anti-banned: jeda dinamis acak (2-4 detik per pesan) + rotasi salam._";
            return $msg;
        }

        // 1b. Tambah User Group Baru: /broadcast addgroup [Nama] = [No1, No2, ...]
        if (preg_match('/^addgroup\s+(.+)$/i', $raw, $matches)) {
            $content = trim($matches[1]);
            $groupName = 'Grup Baru';
            $numString = $content;

            if (str_contains($content, '=')) {
                $parts = explode('=', $content, 2);
                $groupName = trim($parts[0]);
                $numString = trim($parts[1]);
            }

            // Parse nomor kontak (pisahkan dengan koma, spasi, enter)
            $parsedNumbers = preg_split('/[\s,\n]+/', $numString);
            $cleanNumbers = [];
            foreach ($parsedNumbers as $pn) {
                $clean = preg_replace('/[^0-9]/', '', $pn);
                if (strlen($clean) >= 9) {
                    $cleanNumbers[] = $clean;
                }
            }
            $cleanNumbers = array_values(array_unique($cleanNumbers));

            if (empty($cleanNumbers)) {
                return "⚠️ Gagal menambahkan grup. Nomor kontak tidak valid atau kosong.\n\n*Format:* `/broadcast addgroup Nama Grup = 0812345678, 0898765432`";
            }

            $groups[] = [
                'id' => uniqid('grp_'),
                'name' => $groupName,
                'numbers' => $cleanNumbers,
                'count' => count($cleanNumbers),
                'created_at' => now()->toDateTimeString(),
            ];

            \App\Models\Setting::updateOrCreate(
                ['key' => 'wa_broadcast_groups'],
                ['value' => json_encode(array_values($groups))]
            );

            return "✅ *Grup Baru Berhasil Ditambahkan!*\n" .
                   "------------------------------------\n" .
                   "• *Nama Grup*: {$groupName}\n" .
                   "• *Jumlah Kontak*: " . count($cleanNumbers) . " nomor\n" .
                   "• *Nomor Urut Grup*: " . count($groups) . "\n\n" .
                   "💡 _Untuk mengirim pesan ke grup ini, gunakan:_ `/broadcast send " . count($groups) . " [Pesan]`";
        }

        // 1c. Hapus User Group: /broadcast delgroup [Nomor_Grup]
        if (preg_match('/^delgroup\s+(\d+)$/i', $raw, $matches)) {
            $groupIndex = (int) $matches[1] - 1;
            if (!isset($groups[$groupIndex])) {
                return "⚠️ Grup nomor *{$matches[1]}* tidak ditemukan.\nKetik `/broadcast groups` untuk melihat daftar grup yang tersedia.";
            }

            $deletedName = $groups[$groupIndex]['name'] ?? "Grup {$matches[1]}";
            array_splice($groups, $groupIndex, 1);

            \App\Models\Setting::updateOrCreate(
                ['key' => 'wa_broadcast_groups'],
                ['value' => json_encode(array_values($groups))]
            );

            return "🗑️ *Grup Berhasil Dihapus!*\nGrup *{$deletedName}* telah dihapus dari daftar user grup broadcast.";
        }

        // 2. Daftar Grup: /broadcast groups atau /broadcast list
        if (preg_match('/^(groups|group|list)$/i', $raw)) {
            if (empty($groups)) {
                return "ℹ️ Belum ada grup broadcast tersimpan.\nKetik `/broadcast sync` untuk menarik semua kontak customer dari database.";
            }

            $msg = "👥 *DAFTAR GRUP BROADCAST (" . count($groups) . "):*\n";
            $msg .= "------------------------------------\n";
            foreach ($groups as $idx => $grp) {
                $no = $idx + 1;
                $name = $grp['name'] ?? 'Grup';
                $cnt = $grp['count'] ?? count($grp['numbers'] ?? []);
                $msg .= "{$no}. *{$name}* — {$cnt} Nomor\n";
            }
            $msg .= "\n💡 _Kirim broadcast dengan format:_ `/broadcast send [No_Grup] [Pesan]`";
            return $msg;
        }

        // 3. Sinkronkan nomor pelanggan dari database: /broadcast sync
        if (strtolower($raw) === 'sync' || strtolower($raw) === 'import') {
            $numbers = \App\Models\Rental::whereNotNull('no_wa')
                ->where('no_wa', '!=', '')
                ->pluck('no_wa')
                ->map(fn($n) => preg_replace('/[^0-9]/', '', $n))
                ->filter(fn($n) => strlen($n) >= 9)
                ->unique()
                ->values()
                ->toArray();

            // Cek apakah sudah ada grup 'Semua Pelanggan'
            $updated = false;
            foreach ($groups as &$grp) {
                if (str_contains(strtolower($grp['name']), 'pelanggan')) {
                    $grp['numbers'] = $numbers;
                    $grp['count'] = count($numbers);
                    $grp['updated_at'] = now()->toDateTimeString();
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                $groups[] = [
                    'id' => uniqid('grp_'),
                    'name' => 'Semua Pelanggan Rental (' . count($numbers) . ' kontak)',
                    'numbers' => $numbers,
                    'count' => count($numbers),
                    'created_at' => now()->toDateTimeString(),
                ];
            }

            \App\Models\Setting::updateOrCreate(
                ['key' => 'wa_broadcast_groups'],
                ['value' => json_encode(array_values($groups))]
            );

            return "✅ *Sinkronisasi Database Berhasil!*\nTotal *" . count($numbers) . " kontak* nomor pelanggan rental berhasil dimuat ke grup broadcast.";
        }

        // 4. Daftar Template / Shortcut: /broadcast shortcuts
        if (preg_match('/^(shortcuts|shortcut|templates)$/i', $raw)) {
            if (empty($shortcuts)) {
                return "ℹ️ Belum ada shortcut template tersimpan.";
            }

            $msg = "⚡ *DAFTAR TEMPLATE SHORTCUT:* \n";
            $msg .= "------------------------------------\n";
            foreach ($shortcuts as $s) {
                $code = $s['code'] ?? '';
                $title = $s['title'] ?? '';
                $msg .= "• */{$code}* ({$title})\n  \"" . ($s['message'] ?? '') . "\"\n\n";
            }
            return $msg;
        }

        // 5. Eksekusi Pengiriman: /broadcast send [Nomor_Grup] [Pesan / /shortcut]
        if (preg_match('/^send\s+(\d+)\s+(.+)$/is', $raw, $matches)) {
            $groupIndex = (int) $matches[1] - 1;
            $content = trim($matches[2]);

            if (!isset($groups[$groupIndex])) {
                return "⚠️ Grup nomor *{$matches[1]}* tidak ditemukan.\nKetik `/broadcast groups` untuk melihat nomor grup.";
            }

            $targetGroup = $groups[$groupIndex];
            $targetNumbers = $targetGroup['numbers'] ?? [];

            if (empty($targetNumbers)) {
                return "⚠️ Grup *{$targetGroup['name']}* tidak memiliki daftar nomor kontak.";
            }

            // Jika konten adalah shortcut (misal: /promo_weekend)
            $actualMessage = $content;
            if (str_starts_with($content, '/')) {
                $shortcutCode = ltrim($content, '/');
                foreach ($shortcuts as $sc) {
                    if (strtolower($sc['code']) === strtolower($shortcutCode)) {
                        $actualMessage = $sc['message'];
                        break;
                    }
                }
            }

            // Eksekusi pengiriman dengan proteksi anti-banned
            $waService = app(\App\Services\WhatsAppService::class);
            $total = count($targetNumbers);
            $success = 0;
            $failed = 0;

            // Variasi awalan agar pesan tidak identik 100% (anti-spam flag)
            $greetings = ['Halo Kak! 😊', 'Halo Kak,', 'Hai Kak! ✨', 'Halo Kak, salam dari Rent Space!'];

            foreach ($targetNumbers as $i => $num) {
                // Beri variasi salam acak jika pesan dimulai dengan 'Halo Kak'
                $finalMessage = $actualMessage;
                if (str_starts_with($actualMessage, 'Halo Kak')) {
                    $randomGreeting = $greetings[array_rand($greetings)];
                    $finalMessage = preg_replace('/^Halo Kak(!|,\s*|\s*)/', $randomGreeting . ' ', $actualMessage);
                }

                $res = $waService->sendMessage($num, $finalMessage);
                if ($res) {
                    $success++;
                } else {
                    $failed++;
                }

                // JEDA AMAN ANTI-BANNED (Human-like delay):
                // Jeda acak antara 2 sampai 4 detik per nomor
                $delayUs = rand(2000000, 4000000); // 2.0 s/d 4.0 detik
                usleep($delayUs);

                // Tambahan jeda istirahat ekstra setiap 10 pesan (istirahat 5 detik)
                if (($i + 1) % 10 === 0 && ($i + 1) < $total) {
                    sleep(5);
                }
            }

            return "🚀 *BROADCAST SELESAI DIKIRIM!*\n" .
                   "------------------------------------\n" .
                   "• *Target Grup*: {$targetGroup['name']}\n" .
                   "• *Total Kontak*: {$total}\n" .
                   "• *Berhasil Terkirim*: {$success}\n" .
                   "• *Gagal*: {$failed}\n" .
                   "• *Waktu*: " . now()->translatedFormat('d M Y H:i') . " WIB\n\n" .
                   "_Semua pesan terkirim dengan jeda aman anti-banned._";
        }

        return "⚠️ Perintah tidak dikenali. Ketik `/broadcast help` untuk petunjuk lengkap.";
    }

    /**
     * Perintah memori AI di grup report.
     *
     * Fungsinya Supaya tim bisa melihat & mengatur apa yang diingat AI:
     * - /memori            → tampilkan ringkasan yang diingat AI
     * - /memori clear      → hapus histori & fakta (catatan tetap disimpan)
     * - /ingat [catatan]   → simpan catatan permanen yang selalu diingat AI
     *
     * @return string|null null bila teks bukan perintah memori
     */
    private function handleMemoryCommand(string $text, string $actorName, string $groupId): ?string
    {
        $raw = trim($text);
        $lower = mb_strtolower($raw);

        if (!str_starts_with($lower, '/memori') && !str_starts_with($lower, '/ingat') && !str_starts_with($lower, '/lupa')) {
            return null;
        }

        $conv = \App\Services\AiMemoryService::session('wa_group_report', $groupId, $actorName);

        if (str_starts_with($lower, '/ingat')) {
            $note = trim(mb_substr($raw, strlen('/ingat')));
            if ($note === '') {
                return "📌 *Cara pakai:* `/ingat [catatan yang harus diingat AI]`\n\nContoh: `/ingat Unit iPhone 15 Prohit jenis rusak, cek Condition Report dulu sebelum serah terima.`";
            }
            \App\Services\AiMemoryService::pinNote($conv, $note);

            return "✅ *Catatan tersimpan & akan selalu diingat AI*\n\n• {$note}\n\n_Catatan ini ikut di prompt setiap kali AI menjawab di grup ini._";
        }

        if (str_starts_with($lower, '/lupa') || preg_match('/^memori\s+(clear|hapus|reset)$/i', $lower)) {
            \App\Services\AiMemoryService::clearMemory($conv, keepNotes: true);

            return "🧹 *Memori percakapan grup sudah dikosongkan.*\nHistori obrolan & fakta transaksi dihapus, catatan permanen tetap disimpan.";
        }

        if ($lower === '/memori' || $lower === '/memori help' || $lower === '/memori list') {
            $help = "🧠 *MEMORI AI GRUP REPORT*\n";
            $help .= "------------------------------------\n";
            $help .= \App\Services\AiMemoryService::snapshot($conv);
            $help .= "\n\n📋 *Perintah:*\n";
            $help .= "• `/ingat [catatan]` — simpan catatan permanen.\n";
            $help .= "• `/memori` — lihat yang diingat AI.\n";
            $help .= "• `/lupa` — hapus histori & fakta.\n";
            $help .= "\n_Tanya bot seperti biasa dengan @mention, AI akan memakai memori di atas untuk menjawab pertanyaan lanjutan._";

            return $help;
        }

        return "⚠️ Perintah tidak dikenali. Ketik `/memori` untuk melihat perintah yang tersedia.";
    }

    /**
     * Kirim notifikasi / forward permintaan bantuan customer ke WhatsApp Admin
     *
     * Dikirim ke nomor admin sekunder (kalau diisi) dan ke WA admin utama, supaya
     * tetap ada yang tahu walau salah satu nomornya tidak aktif.
     */
    private function notifySecondaryAdmin(string $customerName, ?string $customerPhone, string $message, string $reason = 'minta dibantu admin'): void
    {
        $targets = [];
        $rawTargets = [];

        // 1. Cek apakah ada Grup Khusus Notifikasi (admin_notify_group_id)
        $notifyGroupId = \App\Models\Setting::sanitizeJid(\App\Models\Setting::getVal('admin_notify_group_id', ''));
        if ($notifyGroupId !== '') {
            $targets[] = $notifyGroupId;
        }

        // 2. Ambil admin_wa_secondary (bisa multiple dipisah koma/enter/spasi/titik-koma)
        $secondarySetting = (string) \App\Models\Setting::getVal('admin_wa_secondary', '');
        if ($secondarySetting !== '') {
            $split = preg_split('/[\r\n,;|\s]+/', $secondarySetting, -1, PREG_SPLIT_NO_EMPTY);
            $rawTargets = array_merge($rawTargets, $split);
        }

        // 3. Ambil admin_wa utama jika belum ada grup notifikasi
        if ($notifyGroupId === '') {
            $mainWa = (string) \App\Models\Setting::getVal('admin_wa', '');
            if ($mainWa !== '') {
                $rawTargets[] = $mainWa;
            }
        }

        foreach ($rawTargets as $raw) {
            $rawTrimmed = trim($raw);
            if (str_ends_with($rawTrimmed, '@g.us')) {
                if (!in_array($rawTrimmed, $targets, true)) {
                    $targets[] = $rawTrimmed;
                }
                continue;
            }
            $number = preg_replace('/[^0-9]/', '', $rawTrimmed);
            if ($number !== '' && !in_array($number, $targets, true)) {
                $targets[] = $number;
            }
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $customerPhone);

        $last4 = strlen($cleanPhone) >= 4 ? substr($cleanPhone, -4) : $cleanPhone;
        $simpleGroupMsg = "{$message} ({$customerName} {$last4})";
        $simpleAdminMsg = "{$message} ({$customerName} {$last4})";

        try {
            $waService = app(\App\Services\WhatsAppService::class);

            // 1. Kirim format simpel ke Grup Notifikasi (jika ada)
            if ($notifyGroupId !== '') {
                $waService->sendMessage($notifyGroupId, $simpleGroupMsg);
            }

            // 2. Kirim ke nomor WA pribadi Admin HANYA jika customer minta dibantu admin
            if ($reason === 'minta dibantu admin' && !empty($rawTargets)) {
                foreach ($rawTargets as $target) {
                    $targetNum = preg_replace('/[^0-9]/', '', $target);
                    if ($targetNum !== '') {
                        $waService->sendMessage($targetNum, $simpleAdminMsg);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal forward chat: ' . $e->getMessage());
        }
    }

    /**
     * Ambil target grup dan nomor telepon untuk broadcast (dipanggil oleh Bot Node.js)
     */
    public function getBroadcastTargets(Request $request)
    {
        $apiKey = $request->header('X-API-KEY');
        $expectedKey = config('services.whatsapp.api_key', 'rentspace_secret_wa_token_2026');

        if ($apiKey !== $expectedKey) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $senderJid = $request->query('sender_jid', '');
        $authorizedGroupId = \App\Models\Setting::getVal('admin_wa_group_id', '');

        if (str_ends_with($senderJid, '@g.us')) {
            if (empty($authorizedGroupId) || trim($senderJid) !== trim($authorizedGroupId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses ditolak: Grup ini tidak terdaftar sebagai grup admin resmi.'
                ], 403);
            }
        }

        $groupNumber = (int) $request->query('group', 1);
        $groups = json_decode(\App\Models\Setting::getVal('wa_broadcast_groups', '[]'), true) ?: [];

        $groupIndex = $groupNumber - 1;
        if (!isset($groups[$groupIndex])) {
            return response()->json([
                'status' => false,
                'message' => "Grup nomor {$groupNumber} tidak ditemukan. Ketik /broadcast groups untuk cek daftar grup.",
                'groups' => $groups
            ], 404);
        }

        $targetGroup = $groups[$groupIndex];
        return response()->json([
            'status' => true,
            'group_name' => $targetGroup['name'] ?? 'Grup ' . $groupNumber,
            'numbers' => $targetGroup['numbers'] ?? [],
            'count' => count($targetGroup['numbers'] ?? [])
        ]);
    }
}


