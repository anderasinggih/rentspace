<?php

namespace App\Helpers;

use App\Models\MailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailHelper
{
    /**
     * Send email and log it
     * 
     * @param string|array $to
     * @param \Illuminate\Mail\Mailable $mailable
     * @param string|null $type
     * @return void
     */
    public static function logAndSend($to, $mailable, $type = null)
    {
        $rentalId = null;
        if (isset($mailable->rental)) {
            $rentalId = $mailable->rental->id;
        }

        $recipientStr = is_array($to) ? implode(', ', $to) : $to;
        $subject = $mailable->envelope()->subject;
        $mailableClass = get_class($mailable);

        try {
            Mail::to($to)->send($mailable);
            
            MailLog::create([
                'rental_id' => $rentalId,
                'recipient' => $recipientStr,
                'subject' => $subject,
                'type' => $type ?: $mailableClass,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("MailHelper Error: " . $e->getMessage());
            
            MailLog::create([
                'rental_id' => $rentalId,
                'recipient' => $recipientStr,
                'subject' => $subject,
                'type' => $type ?: $mailableClass,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Queue email and log it
     */
    public static function logAndQueue($to, $mailable, $type = null)
    {
        $rentalId = null;
        if (isset($mailable->rental)) {
            $rentalId = $mailable->rental->id;
        }

        $recipientStr = is_array($to) ? implode(', ', $to) : $to;
        $subject = $mailable->envelope()->subject;
        $mailableClass = $type ?: get_class($mailable);

        // For queue, we log as 'queued' initially? 
        // Actually, let's just log as 'sent' because usually queue works or logs error in worker.
        // But for simplicity of history, 'queued' or 'sent' is fine.
        
        Mail::to($to)->queue($mailable);

        MailLog::create([
            'rental_id' => $rentalId,
            'recipient' => $recipientStr,
            'subject' => $subject,
            'type' => $mailableClass,
            'status' => 'sent', // Or 'queued'
            'sent_at' => now(),
        ]);
    }
}
