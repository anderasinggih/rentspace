<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinnedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'group_jid',
        'sender_phone',
        'sender_name',
        'message_text',
        'is_pinned',
        'pinned_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    /**
     * Konversi format teks khas WhatsApp ke HTML yang aman
     * *tebal* -> <strong>tebal</strong>
     * _miring_ -> <em>miring</em>
     * ~coret~ -> <del>coret</del>
     * ```kode blok``` -> <pre><code>...</code></pre>
     * `kode inline` -> <code>...</code>
     */
    public function getFormattedTextAttribute(): string
    {
        $text = htmlspecialchars($this->message_text ?? '', ENT_QUOTES, 'UTF-8');

        // Kode blok multi-baris ```code```
        $text = preg_replace('/```(.*?)```/s', '<pre class="bg-foreground/5 p-2 rounded-lg my-1 font-mono text-xs overflow-x-auto"><code>$1</code></pre>', $text);

        // Kode inline `code`
        $text = preg_replace('/`([^`\n]+)`/', '<code class="bg-foreground/10 px-1 py-0.5 rounded text-xs font-mono">$1</code>', $text);

        // Bold *text*
        $text = preg_replace('/(?<=\s|^|\W)\*([^\*\n]+)\*(?=\s|$|\W)/u', '<strong class="font-bold text-foreground">$1</strong>', $text);

        // Italic _text_
        $text = preg_replace('/(?<=\s|^|\W)_([^_\n]+)_(?=\s|$|\W)/u', '<em class="italic">$1</em>', $text);

        // Strikethrough ~text~
        $text = preg_replace('/(?<=\s|^|\W)~([^~\n]+)~(?=\s|$|\W)/u', '<del class="line-through opacity-70">$1</del>', $text);

        return nl2br($text);
    }
}


