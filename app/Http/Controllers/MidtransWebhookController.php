<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Mail\PaymentConfirmedNotification;
use App\Mail\OrderCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Catat APAPUN yang masuk dari Midtrans (Sangat detail buat debug)
        Log::info("MIDTRANS WEBHOOK: Masuk", [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'payload' => $request->all()
        ]);

        $payload = $request->all();
        $order_id = trim($payload['order_id'] ?? '');

        if (empty($order_id)) {
            Log::warning("MIDTRANS WEBHOOK: Order ID kosong");
            return response()->json(['message' => 'Empty Order ID'], 200);
        }

        // 2. Ekstrak Booking Code (Format: CODE-TIMESTAMP)
        $parts = explode('-', $order_id);
        $booking_code = trim($parts[0]);

        $status = strtolower(trim($payload['transaction_status'] ?? ''));
        $type = $payload['payment_type'] ?? '';
        $fraud = strtolower(trim($payload['fraud_status'] ?? ''));

        // 3. Cari Data Rental di Database
        $rental = Rental::where('booking_code', $booking_code)->first();

        if (!$rental) {
            Log::error("MIDTRANS WEBHOOK: Rental tidak ditemukan", ['code' => $booking_code, 'id' => $order_id]);
            // Tetap kasih 200 biar Midtrans berhenti "teriak"
            return response()->json(['message' => 'OK - Rental Not Found'], 200);
        }

        // 4. Update Detail Pembayaran
        $updatedDetails = array_merge($rental->payment_details ?? [], $payload);
        $rental->update(['payment_details' => $updatedDetails]);

        // 5. Update Status Rental
        if ($rental->status !== 'paid') {
            if (in_array($status, ['capture', 'settlement'])) {
                if ($fraud == 'challenge') {
                    $rental->update(['status' => 'pending']);
                } else {
                    $rental->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    Log::info("MIDTRANS WEBHOOK: Pembayaran Berhasil untuk Booking Code: " . $booking_code);
                    
                    // Send Email Notification
                    $this->sendEmailNotification($rental, 'paid');
                }
            } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
                $rental->update(['status' => 'cancelled']);
                Log::info("MIDTRANS WEBHOOK: Pembayaran Gagal/Cancel untuk Booking Code: " . $booking_code);
                
                // Send Email Notification
                $this->sendEmailNotification($rental, 'cancelled');
            }
        }

        return response()->json(['message' => 'OK']);
    }

    private function sendEmailNotification($rental, $type)
    {
        $isAdminEmailEnabled = \App\Models\Setting::getVal('is_email_active', '1') == '1';
        $isUserEmailEnabled = \App\Models\Setting::getVal('is_user_email_active', '1') == '1';
        
        if (!$isAdminEmailEnabled && !$isUserEmailEnabled) return;

        try {
            // 1. Prepare recipients
            $emails = [];
            
            if ($isAdminEmailEnabled) {
                $adminEmail = \App\Models\Setting::getVal('admin_email_recipients');
                if (!$adminEmail) {
                    $adminEmail = config('mail.admin_email') ?: config('mail.from.address');
                }
                if ($adminEmail) {
                    $emails = array_merge($emails, array_map('trim', explode(',', $adminEmail)));
                }
            }
            
            if ($isUserEmailEnabled && $rental->email) {
                $emails[] = $rental->email;
            }

            // 2. Send the right notification
            if (!empty($emails)) {
                if ($type === 'paid') {
                    Mail::to($emails)->queue(new PaymentConfirmedNotification($rental));
                } elseif ($type === 'cancelled') {
                    Mail::to($emails)->queue(new OrderCancelledNotification($rental));
                }
            }
        } catch (\Exception $e) {
            Log::error("MIDTRANS WEBHOOK EMAIL FAILED: " . $e->getMessage());
        }
    }
}
