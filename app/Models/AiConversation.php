<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $guarded = ['id'];

    protected $casts = [
        'memory' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id');
    }

    /**
     * Ambil (atau buat) sesi percakapan untuk satu penerima.
     *
     * Sesi hanyaSATU per (channel, identitas) sehingga bot tetap ingat meski
     * cache expiring atau proses bot restart.
     */
    public static function resolve(string $channel, string $peerId, ?string $peerName = null): self
    {
        $peerKey = md5($peerId !== '' ? $peerId : $channel . '|unknown');

        $conv = static::firstOrNew(['channel' => $channel, 'peer_key' => $peerKey]);

        $conv->channel = $channel;
        $conv->peer_key = $peerKey;
        if ($peerName) {
            $conv->peer_name = mb_substr($peerName, 0, 80);
        }
        $conv->memory = $conv->memory ?: [];
        $conv->last_active_at = now();
        $conv->save();

        return $conv;
    }
}
