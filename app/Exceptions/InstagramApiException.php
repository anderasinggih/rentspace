<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error dari Instagram Graph API, diteruskan apa adanya ke admin.
 *
 * Pesan asli dari API disimpan apa adanya karena itu satu-satunya petunjuk
 * yang bisa diterima admin: kode error Graph (mis. 190 = token invalid,
 * 100 = parameter salah) jauh lebih berguna daripada "Gagal kirim".
 */
class InstagramApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload  Body error mentah dari Graph API.
     */
    public function __construct(string $message, public readonly array $payload = [])
    {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromResponse(array $body): self
    {
        $error = $body['error'] ?? null;
        if (!is_array($error)) {
            return new self('Respons Instagram tidak bisa dibaca: ' . substr(json_encode($body) ?: '', 0, 300), $body);
        }

        $message = (string) ($error['message'] ?? 'Instagram menolak permintaan.');
        $code = $error['error_code'] ?? $error['code'] ?? null;
        if ($code !== null) {
            $message .= ' (kode ' . $code . ')';
        }

        return new self($message, $body);
    }
}
