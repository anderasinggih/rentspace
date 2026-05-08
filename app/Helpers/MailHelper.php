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
     * @param int|null $updateLogId
     * @return void
     */
    public static function logAndSend($to, $mailable, $type = null, $updateLogId = null)
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
            
            if ($updateLogId) {
                $log = MailLog::find($updateLogId);
                if ($log) {
                    $log->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'resend_count' => $log->resend_count + 1,
                        'error' => null
                    ]);
                    return;
                }
            }

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
            
            if ($updateLogId) {
                $log = MailLog::find($updateLogId);
                if ($log) {
                    $log->update([
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                        'sent_at' => now(),
                        'resend_count' => $log->resend_count + 1,
                    ]);
                    return;
                }
            }

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
    public static function logAndQueue($to, $mailable, $type = null, $updateLogId = null)
    {
        $rentalId = null;
        if (isset($mailable->rental)) {
            $rentalId = $mailable->rental->id;
        }

        $recipientStr = is_array($to) ? implode(', ', $to) : $to;
        $subject = $mailable->envelope()->subject;
        $mailableClass = $type ?: get_class($mailable);

        Mail::to($to)->queue($mailable);

        if ($updateLogId) {
            $log = MailLog::find($updateLogId);
            if ($log) {
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'resend_count' => $log->resend_count + 1,
                    'error' => null
                ]);
                return;
            }
        }

        MailLog::create([
            'rental_id' => $rentalId,
            'recipient' => $recipientStr,
            'subject' => $subject,
            'type' => $mailableClass,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
