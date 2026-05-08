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

    protected $listeners = ['open-chat' => 'openChat'];

    public function openChat()
    {
        $this->isOpen = true;
    }

    public function mount()
    {
        // Initialize with a welcome message if history is empty
        if (empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'role' => 'model',
                'content' => "Halo Kak! Saya CS AI RENT SPACE. Ada yang bisa saya bantu? Bisa tanya soal stok unit atau status pesanan Kakak ya! 😊"
            ];
        }
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) return;

        $userMsg = $this->message;
        $this->chatHistory[] = ['role' => 'user', 'content' => $userMsg];
        $this->message = '';
        $this->isTyping = true;

        // Determine if it's a specific order status query first (Deterministic)
        $statusInfo = $this->lookupOrderStatus($userMsg);
        
        if ($statusInfo) {
            $this->chatHistory[] = ['role' => 'assistant', 'content' => $statusInfo];
            $this->isTyping = false;
            return;
        }

        // Otherwise, ask the AI Service
        $ai = new AiService();
        $response = $ai->ask($userMsg, $this->chatHistory);

        $this->chatHistory[] = ['role' => 'assistant', 'content' => $response];
        $this->isTyping = false;
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
                
                return "Ketemu Bos! Pesanan untuk **{$unitNames}** statusnya: **{$status}**. Ada lagi yang mau ditanyakan?";
            }
            return "Waduh Bos, NIK/Kode Booking itu nggak ketemu di data saya. Coba dicek lagi ya!";
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
        return view('livewire.front.chat-ai');
    }
}
