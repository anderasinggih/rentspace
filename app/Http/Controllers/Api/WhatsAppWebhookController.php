<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            $this->notifySecondaryAdmin($name, $phone, $text);
            return response()->json(['status' => true, 'message' => 'Admin notified']);
        }

        // 0. Cek Perintah Khusus Admin: /rentspacesettings
        if (str_starts_with(strtolower($text), '/rentspacesettings')) {
            $adminReply = $this->handleRentSpaceSettingsCommand($text, $phone);
            return response()->json(['status' => true, 'reply' => $adminReply]);
        }

        // 1. Cek perintah KATALOG / LIST / DAFTAR HARGA
        if (preg_match('/^(katalog|pricelist|harga|list|daftar\s*harga|1)$/i', $text)) {
            $reply = $this->buildCatalogResponse();
            return response()->json(['status' => true, 'reply' => $reply]);
        }

        // 2. Cek status booking (Format: "CEK RS-XXXX" atau "CEK [KODE]" atau ketik angka 2)
        if (preg_match('/^(cek|status|order)(\s+(.*))?$/i', $text, $matches) || $text === '2') {
            $codeQuery = isset($matches[3]) ? trim($matches[3]) : '';

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
            $aiReply = \App\Services\GeminiAIService::reply($text, $name, $senderJid, $phone);
            if (!empty($aiReply)) {
                return response()->json(['status' => true, 'reply' => $aiReply]);
            }
        }

        return response()->json(['status' => true, 'reply' => null]);
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
        $total = 'Rp ' . number_format($rental->grand_total ?: $rental->total_harga, 0, ',', '.');

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
     * Kirim notifikasi / forward permintaan bantuan customer ke WhatsApp Admin Sekunder
     */
    private function notifySecondaryAdmin(string $customerName, ?string $customerPhone, string $message): void
    {
        $secondaryAdmin = \App\Models\Setting::getVal('admin_wa_secondary');
        if (empty($secondaryAdmin)) {
            return;
        }

        $cleanSecondary = preg_replace('/[^0-9]/', '', $secondaryAdmin);
        if (empty($cleanSecondary)) {
            return;
        }

        $timeStr = now()->translatedFormat('d M Y H:i');
        $phoneInfo = !empty($customerPhone) && !str_starts_with($customerPhone, '375') && strlen($customerPhone) <= 15 
            ? "• *Nomor WA*: {$customerPhone}\n" 
            : "";

        $noticeMsg = "🚨 *NOTIFIKASI PERMINTAAN BANTUAN CUSTOMER* 🚨\n" .
            "------------------------------------\n" .
            "Halo Admin, ada customer di WhatsApp Bot yang minta dihubungkan dengan Admin:\n\n" .
            "• *Nama*: {$customerName}\n" .
            $phoneInfo .
            "• *Waktu*: {$timeStr} WIB\n" .
            "• *Pesan*: \"{$message}\"\n\n" .
            "📱 *MOHON SEGERA BUKA HP TOKO / HP RENT SPACE*\n" .
            "Silakan buka WhatsApp di HP toko untuk segera membalas chat customer ini ya! 🙏\n\n" .
            "_Pesan otomatis dari Bot Rent Space Purwokerto_";

        try {
            app(\App\Services\WhatsAppService::class)->sendMessage($cleanSecondary, $noticeMsg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal forward chat ke admin sekunder: ' . $e->getMessage());
        }
    }
}


