<?php

namespace App\Livewire\Admin;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Setting;
use App\Services\GeminiAIService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithPagination;

class AiMonitor extends Component
{
    use WithPagination;

    public $filterChannel = 'all'; // all, wa_customer, wa_group_report
    public $search = '';
    public $selectedConversationId = null;

    // Direct Test Sandbox
    public $testInput = '';
    public $testChannel = 'wa_customer';
    public $testSenderName = 'Admin Test';
    public $testOutput = '';
    public $testLoading = false;

    // AI Status / Settings
    public $activeModel = '';
    public $botGatewayStatus = 'unknown'; // running, offline, unknown
    public $geminiKeyConfigured = false;
    public $autoReplyCustomer = true;

    protected $queryString = [
        'filterChannel' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        if (!in_array(auth()->user()->role ?? '', ['admin', 'staff'])) {
            abort(403);
        }

        $this->activeModel = Setting::getVal('chatbot_model', GeminiAIService::DEFAULT_MODEL);
        $apiKey = Setting::getVal('chatbot_api_key', config('services.gemini.key', ''));
        $this->geminiKeyConfigured = !empty($apiKey);
        $this->autoReplyCustomer = (bool) Setting::getVal('enable_ai_customer_reply', true);

        $this->checkGatewayStatus();
    }

    public function checkGatewayStatus()
    {
        $botUrl = config('services.whatsapp.url', 'http://localhost:3001');
        try {
            $response = Http::timeout(2)->get("{$botUrl}/status");
            if ($response->successful()) {
                $this->botGatewayStatus = 'running';
            } else {
                $this->botGatewayStatus = 'idle';
            }
        } catch (\Exception $e) {
            $this->botGatewayStatus = 'offline';
        }
    }

    public function toggleCustomerReply()
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Hanya admin yang dapat mengubah pengaturan bot.');
            return;
        }

        $this->autoReplyCustomer = !$this->autoReplyCustomer;
        Setting::setVal('enable_ai_customer_reply', $this->autoReplyCustomer ? '1' : '0');
        session()->flash('success', 'Status Auto-reply AI berhasil diperbarui.');
    }

    public function selectConversation($id)
    {
        $this->selectedConversationId = $id;
    }

    public function closeConversation()
    {
        $this->selectedConversationId = null;
    }

    public function clearConversationHistory($id)
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Akses ditolak.');
            return;
        }

        $conv = AiConversation::find($id);
        if ($conv) {
            $conv->messages()->delete();
            $conv->update(['turn_count' => 0, 'memory' => []]);
            session()->flash('success', 'Histori sesi percakapan berhasil dibersihkan.');
        }
    }

    public function runTestPrompt()
    {
        $this->validate([
            'testInput' => 'required|string|min:2|max:500',
        ]);

        $this->testLoading = true;
        try {
            if ($this->testChannel === 'wa_group_report') {
                $this->testOutput = GeminiAIService::replyInternal($this->testInput, $this->testSenderName);
            } else {
                $this->testOutput = GeminiAIService::replyCustomer(
                    'test-sandbox',
                    $this->testInput,
                    $this->testSenderName,
                    null
                );
            }
        } catch (\Exception $e) {
            $this->testOutput = "Error: " . $e->getMessage();
        } finally {
            $this->testLoading = false;
        }
    }

    public function render()
    {
        // 1. Metrics & Statistics
        $totalSessions = AiConversation::count();
        $activeSessionsToday = AiConversation::where('last_active_at', '>=', Carbon::today())->count();
        $totalMessages = AiMessage::count();
        $totalTokensEstimated = (int) AiConversation::sum('input_tokens');

        // Customer vs Group split
        $customerSessions = AiConversation::where('channel', 'wa_customer')->count();
        $reportGroupSessions = AiConversation::where('channel', 'wa_group_report')->count();

        // 2. Query Conversations list
        $query = AiConversation::with(['messages' => function ($q) {
            $q->latest('id')->limit(1);
        }])->orderByDesc('last_active_at');

        if ($this->filterChannel !== 'all') {
            $query->where('channel', $this->filterChannel);
        }

        if (!empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('peer_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('channel', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(12);

        // 3. Detail Conversation Messages
        $activeConversation = null;
        $messages = collect();
        if ($this->selectedConversationId) {
            $activeConversation = AiConversation::find($this->selectedConversationId);
            if ($activeConversation) {
                $messages = $activeConversation->messages()->orderBy('id', 'asc')->get();
            }
        }

        return view('livewire.admin.ai-monitor', [
            'totalSessions' => $totalSessions,
            'activeSessionsToday' => $activeSessionsToday,
            'totalMessages' => $totalMessages,
            'totalTokensEstimated' => $totalTokensEstimated,
            'customerSessions' => $customerSessions,
            'reportGroupSessions' => $reportGroupSessions,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ])->layout('layouts.admin', ['title' => 'AI Mission Control & Monitoring']);
    }
}
