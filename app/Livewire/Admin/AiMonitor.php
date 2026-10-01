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

    // Bonk / Pentung Disiplin Interactive State
    public $bonkedAgent = null;
    public $bonkMessage = null;
    public $forcedTask = null; // paksa kerja / bangunkan dari istirahat

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

    /**
     * Pentung / Tegur Agen AI agar bangun dari sofa santai dan lanjut kerja!
     */
    public function bonkAgent(string $agentKey, string $actionType = 'work')
    {
        $this->bonkedAgent = $agentKey;

        $agentNames = [
            'cs_bot' => 'Dewi (CS Customer)',
            'core_bot' => 'Singgih (Core Dispatcher)',
            'report_bot' => 'Andera (Report & Finance)',
        ];
        $name = $agentNames[$agentKey] ?? 'Staff';

        if ($actionType === 'break') {
            $this->forcedTask = 'break';
            $this->bonkMessage = "💤 {$name} disuruh istirahat santai di lounge sofa!";
        } else {
            $this->forcedTask = 'work';
            $quotes = [
                "💥 {$name} bergegas: 'Siap bos! Langsung meluncur ke meja komputer!'",
                "⚡ {$name} sigap: 'Monitor ready, langsung balas chat customer!'",
                "☕ {$name} menyeruput kopi: 'Fokus penuh! Semua sistem online!'",
            ];
            $this->bonkMessage = $quotes[array_rand($quotes)];
        }
    }

    public function dismissBonk()
    {
        $this->bonkedAgent = null;
        $this->bonkMessage = null;
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
                $result = GeminiAIService::customerReply(
                    $this->testInput,
                    $this->testSenderName,
                    'test-sandbox@s.whatsapp.net',
                    null
                );
                
                if ($result['handoff']) {
                    $this->testOutput = "⚠️ [Di Luar Topik / Handoff ke Admin]\nAlasan: " . ($result['reason'] ?? 'di luar topik sewa') . "\n\nPesan otomatis diteruskan ke admin.";
                } else {
                    $this->testOutput = $result['reply'] ?? 'Tidak ada respon dari AI.';
                }
            }
        } catch (\Throwable $e) {
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

        // 1.b Aktivitas Real-Time (Cek apakah ada pesan baru dalam 5-10 menit terakhir)
        $latestCustomerMsg = AiMessage::whereHas('conversation', fn($q) => $q->where('channel', 'wa_customer'))
            ->latest('id')
            ->first();

        $latestReportMsg = AiMessage::whereHas('conversation', fn($q) => $q->where('channel', 'wa_group_report'))
            ->latest('id')
            ->first();

        // CS Bot aktif bekerja jika ada pesan customer < 8 menit yang lalu, atau ada order 'work'
        $isCustomerActive = false;
        if ($latestCustomerMsg && $latestCustomerMsg->created_at) {
            $isCustomerActive = $latestCustomerMsg->created_at->diffInMinutes(now()) <= 8;
        }

        // Report Bot aktif jika ada pesan report < 15 menit yang lalu
        $isReportActive = false;
        if ($latestReportMsg && $latestReportMsg->created_at) {
            $isReportActive = $latestReportMsg->created_at->diffInMinutes(now()) <= 15;
        }

        // Terapkan override dari aksi pentung / suruh paksa jika user baru saja klik
        $csStatus = $isCustomerActive ? 'working' : 'break';
        $reportStatus = $isReportActive ? 'working' : 'break';
        $coreStatus = ($isCustomerActive || $isReportActive) ? 'working' : 'break';

        if ($this->forcedTask === 'work') {
            if ($this->bonkedAgent === 'cs_bot') $csStatus = 'working';
            if ($this->bonkedAgent === 'report_bot') $reportStatus = 'working';
            if ($this->bonkedAgent === 'core_bot') $coreStatus = 'working';
        } elseif ($this->forcedTask === 'break') {
            if ($this->bonkedAgent === 'cs_bot') $csStatus = 'break';
            if ($this->bonkedAgent === 'report_bot') $reportStatus = 'break';
            if ($this->bonkedAgent === 'core_bot') $coreStatus = 'break';
        }

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

        // Hanya tampilkan balon percakapan jika pesan benar-benar baru (< 60 detik)
        // Jika sudah lebih dari 1 menit atau sudah terjawab, balon otomatis hilang agar tidak nyangkut terus
        $latestCustomerText = ($latestCustomerMsg && $latestCustomerMsg->created_at && $latestCustomerMsg->created_at->diffInSeconds(now()) <= 60)
            ? \Illuminate\Support\Str::limit($latestCustomerMsg->content, 35) 
            : null;

        $latestReportText = ($latestReportMsg && $latestReportMsg->created_at && $latestReportMsg->created_at->diffInSeconds(now()) <= 60)
            ? \Illuminate\Support\Str::limit($latestReportMsg->content, 35) 
            : null;

        $this->dispatch('ai-status-sync', 
            csStatus: $csStatus, 
            reportStatus: $reportStatus,
            customerBubble: $latestCustomerText,
            reportBubble: $latestReportText
        );

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
            'csStatus' => $csStatus,
            'reportStatus' => $reportStatus,
            'coreStatus' => $coreStatus,
            'latestCustomerMsgTime' => $latestCustomerMsg?->created_at?->diffForHumans() ?? 'Belum ada',
            'latestReportMsgTime' => $latestReportMsg?->created_at?->diffForHumans() ?? 'Belum ada',
            'latestCustomerText' => $latestCustomerText,
            'latestReportText' => $latestReportText,
        ])->layout('layouts.admin', ['title' => 'AI Mission Control & Monitoring']);
    }
}
