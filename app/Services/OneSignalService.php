<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class OneSignalService
{
    /**
     * Send a push notification to all subscribed users.
     *
     * @param string $message The notification message body
     * @param string|null $title The notification title
     * @param string|null $url The URL to open when clicked
     * @return array
     */
    public static function sendToAll($message, $title = null, $url = null)
    {
        $appId = Setting::getVal('onesignal_app_id');
        $apiKey = Setting::getVal('onesignal_rest_api_key');

        if (!$appId || !$apiKey) {
            \Illuminate\Support\Facades\Log::warning('OneSignal Push Aborted: Missing Configuration.', [
                'app_id' => $appId ? 'PRESENT' : 'MISSING',
                'api_key' => $apiKey ? 'PRESENT' : 'MISSING'
            ]);
            return [
                'success' => false,
                'message' => 'OneSignal configuration is missing.'
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $appId,
            'included_segments' => ['Total Subscriptions'], // Menggunakan Total Subscriptions lebih akurat di beberapa kasus
            'target_channel' => 'push',
            'contents' => [
                'en' => $message,
                'id' => $message,
            ],
            'headings' => [
                'en' => $title ?: 'RENT SPACE',
                'id' => $title ?: 'RENT SPACE',
            ],
            'isAnyWeb' => true,
            'url' => $url ?: config('app.url'),
            'priority' => 10, // High Priority
            'web_push_priority' => 'high',
            'ttl' => 3600, // 1 jam masa tunggu jika HP offline
        ]);

        if ($response->successful()) {
            \Illuminate\Support\Facades\Log::info('OneSignal Push Success:', $response->json());
            return [
                'success' => true,
                'data' => $response->json()
            ];
        }

        \Illuminate\Support\Facades\Log::error('OneSignal Push Failed:', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return [
            'success' => false,
            'message' => $response->body()
        ];
    }
    /**
     * Send a push notification specifically to Admins.
     */
    public static function sendToAdmins($message, $title = null, $url = null)
    {
        $appId = Setting::getVal('onesignal_app_id');
        $apiKey = Setting::getVal('onesignal_rest_api_key');

        if (!$appId || !$apiKey) return ['success' => false];

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $appId,
            'filters' => [
                ['field' => 'tag', 'key' => 'role', 'relation' => '=', 'value' => 'admin'],
                // OR (Staff juga bisa dapet kalau mau)
                // ['operator' => 'OR'],
                // ['field' => 'tag', 'key' => 'role', 'relation' => '=', 'value' => 'staff'],
            ],
            'contents' => [
                'en' => $message,
                'id' => $message,
            ],
            'headings' => [
                'en' => $title ?: 'RENT SPACE',
                'id' => $title ?: 'RENT SPACE',
            ],
            'url' => $url ?: route('admin.monitoring'),
            'priority' => 10,
            'web_push_priority' => 'high',
        ]);

        return ['success' => $response->successful()];
    }
}
