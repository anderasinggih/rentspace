<?php

namespace App\Console\Commands;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PruneAiMemory extends Command
{
    protected $signature = 'app:prune-ai-memory {--days=30 : Umur chat customer sebelum dihapus}';

    protected $description = 'Bersihkan histori memori AI yang sudah lama (chat customer), catatan tim tetap disimpan';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays(max(1, $days));

        $this->info("Membersihkan memori AI lebih dari {$days} hari (sebelum {$cutoff->toDateTimeString()})...");

        // Sesi yang masih punya catatan tim tidak dihapus, hanya history-nya.
        $conversations = AiConversation::where('last_active_at', '<', $cutoff)->get();
        $deletedSessions = 0;
        $deletedMessages = 0;

        foreach ($conversations as $conv) {
            $hasNotes = !empty($conv->memory['notes'] ?? []);
            $deletedMessages += AiMessage::where('ai_conversation_id', $conv->id)->delete();
            if (! $hasNotes) {
                $conv->delete();
                $deletedSessions++;
            }
        }

        $this->info("Sesi dihapus: {$deletedSessions} | Pesan dihapus: {$deletedMessages}");
        $this->info('Catatan tim (memory->notes) tetap disimpan.');

        return self::SUCCESS;
    }
}
