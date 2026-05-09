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
            'included_segments' => ['All'],
            'contents' => ['en' => $message, 'id' => $message],
            'headings' => ['en' => $title ?: 'RENT SPACE', 'id' => $title ?: 'RENT SPACE'],
            'url' => $url ?: config('app.url'),
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json()
            ];
        }

        return [
            'success' => false,
            'message' => $response->body()
        ];
    }
}
