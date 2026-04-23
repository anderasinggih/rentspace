<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info("Midtrans Webhook Received", ['payload' => $request->all()]);
        
        $payload = $request->all();
        
        // Extract original booking code from order_id (format: CODE-TIMESTAMP)
        $order_id = $payload['order_id'] ?? '';
        $parts = explode('-', $order_id);
        $booking_code = $parts[0];
        
        $status = $payload['transaction_status'] ?? '';
        $type = $payload['payment_type'] ?? '';
        $fraud = $payload['fraud_status'] ?? '';

        $rental = Rental::where('booking_code', $booking_code)->first();

        if (!$rental) {
            Log::error("Rental not found for booking code: " . $booking_code);
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $rental->update(['status' => 'pending']);
                } else {
                    $rental->update(['status' => 'paid']);
                }
            }
        } elseif ($status == 'settlement') {
            $rental->update(['status' => 'paid']);
        } elseif ($status == 'pending') {
            $rental->update(['status' => 'pending']);
        } elseif ($status == 'deny' || $status == 'expire' || $status == 'cancel') {
            $rental->update(['status' => 'cancelled']);
        }

        return response()->json(['message' => 'OK']);
    }
}
