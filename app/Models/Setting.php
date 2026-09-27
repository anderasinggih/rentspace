<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];

    public static function getVal($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Bersihkan ID Grup WhatsApp dari karakter tak terlihat.
     *
     * WhatsApp menyisipkan karakter seperti U+202F / U+2060 / U+FEFF saat ID
     * disalin dari chat. PHP trim() hanya menghapus " \t\n\r\0\x0B" sehingga
     * karakter tersebut bertahan dan membuat perbandingan JID selalu gagal.
     */
    public static function sanitizeJid($value): string
    {
        return trim(preg_replace('/[^\x20-\x7E]/', '', (string) $value));
    }
}
