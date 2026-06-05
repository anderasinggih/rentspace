<?php

namespace App\Livewire\Front;

use Livewire\Component;
use App\Services\AiService;
use App\Models\Rental;
use Illuminate\Support\Facades\Session;

class ChatAi extends Component
{
    public $isOpen = false;
    public $message = '';
    public $chatHistory = [];
    public $isTyping = false;
    public $spamUntil = 0;

    protected $listeners = ['open-chat' => 'openChat'];

    public function openChat()
    {
        $this->isOpen = true;
    }

    public function mount()
    {
        // Load history from session if exists and not expired
        $lastChatTime = Session::get('chat_ai_last_activity');
        if ($lastChatTime && now()->diffInMinutes($lastChatTime) < 15) {
            $this->chatHistory = Session::get('chat_ai_history', []);
        }

        $spamUntil = Session::get('chat_ai_spam_until');
        $this->spamUntil = $spamUntil ? $spamUntil->timestamp * 1000 : 0;

        // Initialize with a welcome message if history is empty
        if (empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => "Halo Kak! Saya CS AI RENT SPACE. Ada yang bisa saya bantu? Bisa tanya soal stok unit atau status pesanan Kakak ya! 😊"
            ];
            $this->saveToSession();
        }
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) return;

        // Check if user is currently under cooldown
        $spamUntil = Session::get('chat_ai_spam_until');
        if ($spamUntil && now()->lessThan($spamUntil)) {
            $secondsLeft = round(now()->diffInSeconds($spamUntil));
            $this->chatHistory[] = [
                'role' => 'model', 
                'content' => "Sabar ya Kak, tunggu **{$secondsLeft} detik** lagi baru bisa kirim pesan. Kalau ada kendala mendesak, silakan [CHAT_WA] ya! 😊"
            ];
            $this->message = '';
            $this->saveToSession();
            $this->dispatch('scroll-bottom');
            $this->dispatch('chat-received');
            return;
        }

        // Check for Spam and apply cooldown if detected
        if ($this->isSpam($this->message)) {
            $cooldownTime = now()->addSeconds(30);
            Session::put('chat_ai_spam_until', $cooldownTime);
            $this->spamUntil = $cooldownTime->timestamp * 1000;

            $this->chatHistory[] = ['role' => 'user', 'content' => $this->message];
            $this->chatHistory[] = [
                'role' => 'model', 
                'content' => "Waduh Kak, ngetiknya kecepetan! Fitur chat dikunci selama **30 detik** ya. Silakan [CHAT_WA] atau coba lagi nanti. 😊"
            ];
            $this->message = '';
            $this->saveToSession();
            $this->dispatch('scroll-bottom');
            $this->dispatch('chat-received');
            return;
        }

        $userMsg = $this->message;
        $this->chatHistory[] = ['role' => 'user', 'content' => $userMsg];
        $this->message = '';
        $this->isTyping = true;
        $this->saveToSession();
        
        // Dispatch event to process AI response in a separate request to keep UI responsive
        $this->dispatch('process-ai');
        $this->dispatch('scroll-bottom');
        $this->dispatch('chat-sent');
    }

    private function isSpam($msg)
    {
        $now = now();
        $timestamps = Session::get('chat_ai_msg_timestamps', []);
        
        // 1. Rate Limit Check: Max 3 messages in 5 seconds
        $timestamps = array_filter($timestamps, fn($t) => $now->diffInSeconds($t) < 5);
        $timestamps[] = $now;
        Session::put('chat_ai_msg_timestamps', $timestamps);

        if (count($timestamps) > 3) {
            return true;
        }

        // 2. Repetitive Content Check: Same message more than 2 times in a row
        $lastMsg = Session::get('chat_ai_last_msg');
        $repeatCount = Session::get('chat_ai_repeat_count', 0);

        if ($lastMsg === $msg) {
            $repeatCount++;
        } else {
            $repeatCount = 1;
        }

        Session::put('chat_ai_last_msg', $msg);
        Session::put('chat_ai_repeat_count', $repeatCount);

        if ($repeatCount > 2) {
            return true;
        }

        return false;
    }

    #[\Livewire\Attributes\On('process-ai')]
    public function getAiResponse()
    {
        $lastMsg = end($this->chatHistory);
        if ($lastMsg['role'] !== 'user') return;

        $userMsg = $lastMsg['content'];

        // Determine if it's a specific order status query first (Deterministic)
        $statusInfo = $this->lookupOrderStatus($userMsg);
        
        if ($statusInfo) {
            $this->chatHistory[] = ['role' => 'model', 'content' => $statusInfo];
            $this->isTyping = false;
            $this->dispatch('chat-received');
            return;
        }

        // Otherwise, ask the AI Service
        $ai = new AiService();
        $response = $ai->ask($userMsg, array_slice($this->chatHistory, 0, -1));

        $this->chatHistory[] = ['role' => 'model', 'content' => $response];
        $this->isTyping = false;
        $this->saveToSession();
        $this->dispatch('scroll-bottom');
        $this->dispatch('chat-received');
    }

    private function saveToSession()
    {
        Session::put('chat_ai_history', $this->chatHistory);
        Session::put('chat_ai_last_activity', now());
    }

    /**
     * Quick deterministic lookup for order status if user provides NIK or Booking Code
     */
    protected function lookupOrderStatus($text)
    {
        $text = strtoupper($text);
        
        // Regex for Booking Code (usually 8-10 chars like RS-XXXXX)
        preg_match('/RS-[A-Z0-9]+/', $text, $matchesCode);
        // Regex for NIK (16 digits)
        preg_match('/[0-9]{16}/', $text, $matchesNik);

        $query = null;
        if (!empty($matchesCode)) $query = ['booking_code', $matchesCode[0]];
        elseif (!empty($matchesNik)) $query = ['nik', $matchesNik[0]];

        if ($query) {
            $rental = Rental::where($query[0], $query[1])->latest()->first();
            if ($rental) {
                $statusMap = [
                    'pending' => 'Menunggu Pembayaran ⏳',
                    'success' => 'Lunas & Siap/Sedang Disewa ✅',
                    'cancelled' => 'Dibatalkan ❌',
                    'completed' => 'Sudah Selesai (Selesai Sewa) 🏁'
                ];
                $status = $statusMap[$rental->status] ?? $rental->status;
                $unitNames = $rental->units->pluck('name')->implode(', ');
                
                return "Ketemu Kak! Pesanan untuk **{$unitNames}** statusnya: **{$status}**. Ada lagi yang mau ditanyakan?";
            }
            return "Waduh Kak, NIK/Kode Booking itu nggak ketemu di data saya. Coba dicek lagi ya!";
        }

        return null;
    }

    public function quickAction($action)
    {
        $this->message = $action;
        $this->sendMessage();
    }

    public function render()
    {
        $recommendations = \App\Models\Unit::inRandomOrder()->take(2)->get();
        
        $isBlocked = $this->spamUntil > (now()->timestamp * 1000);

        return view('livewire.front.chat-ai', [
            'recommendations' => $recommendations,
            'isBlocked' => $isBlocked
        ]);
    }
}
