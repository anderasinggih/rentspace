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
            Log::error("Midtrans Webhook: Rental not found", ['booking_code' => $booking_code, 'order_id' => $order_id]);
            return response()->json(['message' => 'Order not found or Webhook testing'], 200);
        }

        // Gabungkan data lama dengan data baru dari Midtrans
        $updatedDetails = array_merge($rental->payment_details ?? [], $payload);
        $rental->update(['payment_details' => $updatedDetails]);

        // Proteksi: Jika sudah PAID, jangan dirubah lagi statusnya kecuali oleh admin
        if ($rental->status === 'paid') {
            Log::info("Midtrans Webhook: Rental already paid, ignoring status update", ['booking_code' => $booking_code]);
            return response()->json(['message' => 'Already Paid']);
        }

        if ($status == 'capture' || $status == 'settlement') {
            if ($fraud == 'challenge') {
                $rental->update(['status' => 'pending']);
            } else {
                $rental->update(['status' => 'paid']);
            }
        } elseif ($status == 'pending') {
            $rental->update(['status' => 'pending']);
        } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
            $rental->update(['status' => 'cancelled']);
            Log::warning("Midtrans Webhook: Transaction closed", ['booking_code' => $booking_code, 'status' => $status]);
        }

        return response()->json(['message' => 'OK']);
    }
}
