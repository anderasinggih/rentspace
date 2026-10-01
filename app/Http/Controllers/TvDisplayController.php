<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\GeminiAIService;
use App\Services\TvMusicFeed;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class TvDisplayController extends Controller
{
    public function __construct(private TvMusicFeed $feed) {}

    public function show(): View
    {
        return view('tv.display', [
            'refreshSeconds' => max(5, (int) config('tv.refresh_seconds', 10)),
            'sourceNames' => array_values(array_unique(array_map(
                fn (array $s) => (string) ($s['name'] ?? 'YouTube'),
                (array) config('tv.sources', [])
            ))),
        ]);
    }

    public function tracks(): JsonResponse
    {
        return response()->json([
            'tracks' => $this->feed->tracks(),
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function stats(): JsonResponse
    {
        $activeWindowMinutes = 2;

        $latestCustomer = AiMessage::whereHas('conversation', fn ($q) => $q->where('channel', 'wa_customer'))
            ->latest('id')->value('created_at');

        $latestReport = AiMessage::whereHas('conversation', fn ($q) => $q->where('channel', 'wa_group_report'))
            ->latest('id')->value('created_at');

        $csWorking = $latestCustomer && Carbon::parse($latestCustomer)->diffInMinutes(now()) <= $activeWindowMinutes;
        $reportWorking = $latestReport && Carbon::parse($latestReport)->diffInMinutes(now()) <= $activeWindowMinutes;

        $unitIds = RentalItem::query()
            ->whereHas('rental', fn ($q) => $q->whereIn('status', ['paid', 'renting'])->where('waktu_selesai', '>=', now()))
            ->distinct()->pluck('unit_id');

        $activeUnits = Unit::where('is_active', true)->count();

        $paidStatuses = ['paid', 'renting', 'completed'];

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'ai' => [
                'model' => Setting::getVal('chatbot_model', GeminiAIService::DEFAULT_MODEL),
                'key_ok' => ! empty(Setting::getVal('chatbot_api_key', config('services.gemini.key', ''))),
                'gateway' => $this->gatewayStatus(),
                'auto_reply' => (bool) Setting::getVal('enable_ai_customer_reply', true),
                'sessions' => AiConversation::count(),
                'sessions_today' => AiConversation::where('last_active_at', '>=', Carbon::today())->count(),
                'messages' => AiMessage::count(),
                'tokens' => (int) AiConversation::sum('input_tokens'),
                'cs' => $csWorking ? 'working' : 'break',
                'report' => $reportWorking ? 'working' : 'break',
                'core' => ($csWorking || $reportWorking) ? 'working' : 'break',
                'last_message_at' => $latestCustomer ? Carbon::parse($latestCustomer)->toIso8601String() : null,
            ],
            'units' => [
                'total' => Unit::count(),
                'active' => $activeUnits,
                'rented' => $unitIds->count(),
                'available' => max(0, $activeUnits - $unitIds->count()),
            ],
            'rentals' => [
                'today_count' => Rental::whereDate('created_at', Carbon::today())->count(),
                'today_revenue' => (float) Rental::whereIn('status', $paidStatuses)
                    ->whereDate('paid_at', Carbon::today())->sum('grand_total'),
                'active_count' => Rental::whereIn('status', ['paid', 'renting'])
                    ->where('waktu_selesai', '>=', now())->count(),
                'pending_count' => Rental::where('status', 'pending')->count(),
                'month_revenue' => (float) Rental::whereIn('status', $paidStatuses)
                    ->where('paid_at', '>=', Carbon::now()->startOfMonth())->sum('grand_total'),
            ],
        ])->header('Cache-Control', 'no-store');
    }

    protected function gatewayStatus(): string
    {
        return Cache::remember('tv.gateway.status', now()->addSeconds(20), function () {
            $url = rtrim((string) config('services.whatsapp.url', 'http://localhost:3001'), '/');

            try {
                return Http::timeout(2)->get("{$url}/status")->successful() ? 'running' : 'idle';
            } catch (\Throwable $e) {
                return 'offline';
            }
        });
    }
}