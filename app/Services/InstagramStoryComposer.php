<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Unit;

/**
 * Penyusun file story Instagram (1080x1920, rasio 9:16).
 *
 * Semuanya dikerjakan tanpa pustaka eksternal: GD + font TTF yang di-*bundle* di
 * `resources/fonts/`. Alasannya cerita ini dirakit di web server shared
 * hosting yang tidak bisa `composer require` paket imagemagick, dan tetap
 * harus jalan kalau composer tidak bisa dijalankan.
 *
 * Catatan: `resources/fonts/font.ttf` yang sudah ada di repo ini sebenarnya
 * file HTML, bukan font — makanya font TTF asli ikut di-*bundle* di sini.
 *
 * Batasan Instagram untuk story: PNG/JPG, minimal 3 detik, maksimal 10 frame
 * per set, dan lebar 1080px adalah nilai yang aman untuk semua rasio.
 */
class InstagramStoryComposer
{
    public const WIDTH = 1080;
    public const HEIGHT = 1920;

    /** Batas ukuran file story Instagram (8 MB). */
    public const MAX_BYTES = 8 * 1024 * 1024;

    protected Unit $unit;
    protected array $tokens;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
        $this->tokens = $this->tokens();
    }

    /**
     * Placeholder yang bisa dipakai di template caption maupun template teks
     * di dalam gambar. Dipisah dari logika render supaya admin bisa menambah
     * template tanpa menyentuh kode.
     *
     * @return array<string, string>
     */
    public function tokens(): array
    {
        $link = rtrim((string) config('app.url'), '/') . '/';
        $address = (string) (Setting::getVal('admin_address') ?: 'Purwokerto');
        $igName = trim((string) Setting::getVal('social_ig_name', ''), '@');

        $specParts = array_filter([
            $this->unit->warna,
            $this->unit->memori,
        ]);

        return [
            '{nama}' => (string) ($this->unit->seri ?: 'Unit Rental'),
            '{nama_lengkap}' => (string) $this->unit->nama_lengkap,
            '{kategori}' => (string) ($this->unit->category?->name ?: ucfirst((string) ($this->unit->kategori ?: 'Unit'))),
            '{spesifikasi}' => implode(' ', $specParts),
            '{harga_hari}' => $this->price($this->unit->harga_per_hari, 'hari'),
            '{harga_jam}' => $this->price($this->unit->harga_per_jam, 'jam'),
            '{lokasi}' => $address,
            '{link}' => $link,
            '{ig}' => $igName !== '' ? '@' . $igName : '',
            '{tanggal}' => now()->translatedFormat('d M Y'),
        ];
    }

    /**
     * Substitusi placeholder. Placeholder yang tidak dikenal dibiarkan apa
     * adanya supaya admin bisa langsung melihat salah ketik di template.
     */
    public function applyTemplate(?string $template): string
    {
        $template = (string) $template;
        if (trim($template) === '') {
            return '';
        }

        $replaced = strtr($template, $this->tokens);

        // Rapikan bar kosong berlebih yang muncul kalau placeholder opsional
        // (mis. {ig}) tidak terisi.
        return trim(preg_replace("/[ \t]+\n/", "\n", preg_replace("/\n{3,}/", "\n\n", $replaced)));
    }

    public function defaultCaptionTemplate(): string
    {
        return "{nama} {spesifikasi}\n{harga_hari} · {harga_jam}\n\n📍 {lokasi}\nBooking: {link}";
    }

    public function defaultOverlayTemplate(): string
    {
        return "{nama}\n{spesifikasi}\n{harga_hari} · {harga_jam}";
    }

    public function caption(): string
    {
        $template = Setting::getVal('ig_story_caption_template');
        if (trim((string) $template) === '') {
            $template = $this->defaultCaptionTemplate();
        }

        // Instagram memotong caption di 2.200 karakter.
        return mb_substr(trim($this->applyTemplate($template)), 0, 2200);
    }

    public function overlay(): string
    {
        $template = Setting::getVal('ig_story_overlay_template');
        if (trim((string) $template) === '') {
            $template = $this->defaultOverlayTemplate();
        }

        return $this->applyTemplate($template);
    }

    /**
     * Render story dan simpan ke folder publik `uploads/ig-story/`.
     *
     * File WAJIB berada di URL yang bisa diakses Instagram, jadi disimpan lewat
     * web root — bukan folder privat. Kalau `public/` tidak bisa ditulis, ambil
     * lokasi persis seperti halaman pengaturan sudah lakukan.
     *
     * @return array{path: string, url: string, bytes: int}
     */
    public function render(): array
    {
        $image = $this->canvas();
        $this->drawPhoto($image);
        $this->drawOverlay($image);
        $this->drawFooter($image);

        $dir = $this->outputDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = 'story-' . ($this->unit->id ?: 'x') . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.png';
        $path = $dir . '/' . $name;

        imagepng($image, $path, 6);
        imagedestroy($image);

        if (!is_file($path)) {
            throw new \RuntimeException('Gagal menulis file story ke ' . $dir);
        }

        $bytes = (int) filesize($path);
        if ($bytes > self::MAX_BYTES) {
            @unlink($path);

            throw new \RuntimeException('Ukuran story ' . $this->humanBytes($bytes) . ' melebihi batas Instagram (' . $this->humanBytes(self::MAX_BYTES) . ').');
        }

        return [
            'path' => $path,
            'url' => $this->publicUrlFor($name),
            'bytes' => $bytes,
        ];
    }

    /**
     * Lokasi folder story. Mengikuti pola `uploads/` yang sudah dipakai
     * pengaturan (penting untuk shared hosting: `storage/app/public` belum
     * tentu Termin symlink-nya aktif).
     */
    public function outputDir(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? public_path();
        if (!is_dir($root) || !is_writable($root)) {
            $root = public_path();
        }

        return rtrim((string) $root, '/') . '/uploads/ig-story';
    }

    public function publicUrlFor(string $filename): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return ($base !== '' ? $base : '') . '/uploads/ig-story/' . $filename;
    }

    /**
     * Kanvas dasar: gradien gelap di atas, panel teks di bawah.
     */
    protected function canvas(): \GdImage
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($image, true);
        imagesavealpha($image, false);

        [$top, $bottom] = $this->gradient();
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $r = (int) ($top[0] + ($bottom[0] - $top[0]) * $y / self::HEIGHT);
            $g = (int) ($top[1] + ($bottom[1] - $top[1]) * $y / self::HEIGHT);
            $b = (int) ($top[2] + ($bottom[2] - $top[2]) * $y / self::HEIGHT);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, self::WIDTH, $y, $color);
        }

        return $image;
    }

    /**
     * @return array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}}
     */
    protected function gradient(): array
    {
        $hex = trim((string) (Setting::getVal('ig_story_theme') ?: '#0f172a'));
        $rgb = $this->hexToRgb($hex) ?: [15, 23, 42];

        // Gradien halus: dari warna tema 20% lebih terang di atas ke warna
        // tema 35% lebih gelap di bawah, supaya teks putih selalu kontras.
        $top = array_map(fn ($c) => (int) min(255, $c + 51), $rgb);
        $bottom = array_map(fn ($c) => (int) max(0, $c - 89), $rgb);

        return [$top, $bottom];
    }

    /**
     * Foto unit memenuhi area atas dengan rasio 9:16, lalu diberi gradasi
     * supaya panel teks di bawah menyambung mulus. Kalau unit belum punya
     * foto, dipakai logo publik sebagai gantinya — story tetap tayang.
     */
    protected function drawPhoto(\GdImage $image): void
    {
        $boxH = 1180;
        $source = $this->photoSource();

        if ($source === null) {
            $this->drawPlaceholder($image, $boxH);

            return;
        }

        $src = $this->load($source);
        if ($src === null) {
            $this->drawPlaceholder($image, $boxH);

            return;
        }

        $crop = $this->coverCrop($src, self::WIDTH, $boxH);
        imagecopy($image, $crop, 0, 0, 0, 0, self::WIDTH, $boxH);
        imagedestroy($crop);
        imagedestroy($src);

        // Gradasi gelap di bawah foto supaya teks tidak tenggelam.
        for ($y = $boxH - 520; $y < $boxH; $y++) {
            $alpha = (int) (127 * (($y - ($boxH - 520)) / 520));
            $shade = imagecolorallocatealpha($image, 0, 0, 0, max(0, min(127, $alpha)));
            imagefilledrectangle($image, 0, $y, self::WIDTH, $y, $shade);
        }
    }

    protected function drawPlaceholder(\GdImage $image, int $boxH): void
    {
        $panel = imagecolorallocatealpha($image, 255, 255, 255, 105);
        imagefilledrectangle($image, 0, 0, self::WIDTH, $boxH, $panel);

        $logo = $this->load(public_path('logo.png'));
        if ($logo !== null) {
            $w = imagesx($logo);
            $h = imagesy($logo);
            $scale = min(420 / $w, 420 / $h);
            $size = imagecreatetruecolor((int) ($w * $scale), (int) ($h * $scale));
            imagealphablending($size, false);
            imagesavealpha($size, true);
            imagecopyresampled($size, $logo, 0, 0, 0, 0, imagesx($size), imagesy($size), $w, $h);
            imagecopy($image, $size, (int) ((self::WIDTH - imagesx($size)) / 2), (int) (($boxH - imagesy($size)) / 2), 0, 0, imagesx($size), imagesy($size));
            imagedestroy($size);
            imagedestroy($logo);
        }
    }

    /**
     * Panel teks utama. Baris template dibungkus otomatis supaya nama unit
     * panjang tidak keluar dari kanvas.
     */
    protected function drawOverlay(\GdImage $image): void
    {
        $lines = $this->wrap($this->overlay(), 900, 62, 3);

        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 203, 213, 225);

        $y = 1230;
        foreach ($lines as $i => $line) {
            $isTitle = $i === 0;
            $font = $this->font($isTitle ? 'bold' : 'semibold');
            $size = $isTitle ? 62 : 42;
            $color = $isTitle ? $white : $muted;

            foreach ($this->wrap($line, 900, $size, 1) as $wrapped) {
                imagettftext($image, $size, 0, 90, $y, $color, $font, $wrapped);
                $y += $isTitle ? 82 : 60;
            }
        }

        // Garis aksen tipis sebagai pemisah.
        $accent = imagecolorallocate($image, 56, 189, 248);
        imagefilledrectangle($image, 90, $y + 8, 90 + 120, $y + 14, $accent);

        // Blok harga dicetak besar supaya jadi titik pandang pertama.
        $y += 92;
        $priceColor = imagecolorallocate($image, 255, 255, 255);
        foreach ($this->priceLines() as $line) {
            imagettftext($image, 52, 0, 90, $y, $priceColor, $this->font('bold'), $line);
            $y += 68;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function priceLines(): array
    {
        $day = $this->price($this->unit->harga_per_hari, 'hari');
        $hour = $this->price($this->unit->harga_per_jam, 'jam');

        if ($day === '' && $hour === '') {
            return ['Harga konnte ditanyakan'];
        }

        $lines = array_values(array_filter([$day, $hour]));

        return count($lines) > 1 ? [implode('  ·  ', $lines)] : $lines;
    }

    /**
     * Baris bawah: lokasi + handle Instagram.
     */
    protected function drawFooter(\GdImage $image): void
    {
        $tokens = $this->tokens;
        $muted = imagecolorallocate($image, 148, 163, 184);

        $y = self::HEIGHT - 210;
        $footer = trim(($tokens['{lokasi}'] !== '' ? '📍 ' . $tokens['{lokasi}'] : ''));
        if ($footer !== '') {
            foreach ($this->wrap($footer, 900, 36, 2) as $line) {
                imagettftext($image, 36, 0, 90, $y, $muted, $this->font('regular'), $line);
                $y += 50;
            }
        }

        if ($tokens['{ig}'] !== '') {
            imagettftext($image, 32, 0, 90, self::HEIGHT - 110, $muted, $this->font('regular'), $tokens['{ig}']);
        }
    }

    /**
     * Potong foto asli jadi rasio target dengan titik tengah, lalu diskalakan.
     * Kalau foto lebih kecil dari yang diminta,GD akan menskalakan ke atas —
     *story tetap terlihat benar walau sumbernya resolusi kecil.
     */
    protected function coverCrop(\GdImage $src, int $targetW, int $targetH): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $targetRatio = $targetW / $targetH;

        if ($w / $h > $targetRatio) {
            $cropH = $h;
            $cropW = (int) round($h * $targetRatio);
        } else {
            $cropW = $w;
            $cropH = (int) round($w / $targetRatio);
        }

        $cropX = (int) max(0, ($w - $cropW) / 2);
        $cropY = (int) max(0, ($h - $cropH) / 2);

        $out = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($out, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);

        return $out;
    }

    /**
     * Muat file gambar dari disk dengan benar-benar mengecek hasilnya, bukan
     * cuma `file_exists` — file 0 byte atau HTML salah tempat akan membuat
     * `imagecreatefrom*` return false.
     */
    protected function load(string $path): ?\GdImage
    {
        if (!is_file($path) || filesize($path) < 1) {
            return null;
        }

        $image = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'gif' => @imagecreatefromgif($path),
            default => @imagecreatefromjpeg($path),
        };

        return $image ?: null;
    }

    protected function photoSource(): ?string
    {
        $foto = trim((string) $this->unit->foto);
        if ($foto === '') {
            return null;
        }

        if (str_starts_with($foto, 'http://') || str_starts_with($foto, 'https://')) {
            return null; // URL eksternal tidak diambil server-side.
        }

        foreach ($this->uploadRoots() as $root) {
            $candidate = rtrim($root, '/') . '/uploads/unit/' . $foto;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function uploadRoots(): array
    {
        $roots = [public_path()];
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $roots[] = (string) $_SERVER['DOCUMENT_ROOT'];
        }

        return array_values(array_unique($roots));
    }

    /**
     * Bungkus teks per baris template, lalu gabung jadi maksimal N baris supaya
     * panel teks tidak meluber melewati kanvas.
     *
     * @return array<int, string>
     */
    protected function wrap(string $text, int $maxWidth, int $fontSize, int $maxLines): array
    {
        $font = $this->font($fontSize >= 56 ? 'bold' : 'semibold');
        $result = [];

        foreach (preg_split('/\R/', trim($text)) ?: [] as $rawLine) {
            $words = preg_split('/\s+/', trim($rawLine)) ?: [];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                if ($this->textWidth($candidate, $fontSize, $font) <= $maxWidth || $current === '') {
                    $current = $candidate;
                    continue;
                }

                $result[] = $current;
                $current = $word;
            }

            if ($current !== '') {
                $result[] = $current;
            }
        }

        if (count($result) > $maxLines) {
            $result = array_slice($result, 0, $maxLines);
            $last = array_key_last($result);
            while ($this->textWidth($result[$last] . '...', $fontSize, $font) > $maxWidth && strlen($result[$last]) > 4) {
                $result[$last] = substr($result[$last], 0, -1);
            }
            $result[$last] .= '...';
        }

        return $result;
    }

    protected function textWidth(string $text, int $fontSize, string $font): int
    {
        $box = @imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return mb_strlen($text) * (int) ($fontSize * 0.55);
        }

        return $box[2] - $box[0];
    }

    protected function font(string $weight): string
    {
        $file = match ($weight) {
            'bold' => 'poppins-bold.ttf',
            'semibold' => 'poppins-semibold.ttf',
            default => 'poppins-regular.ttf',
        };

        $path = resource_path('fonts/' . $file);

        if (!is_file($path)) {
            throw new \RuntimeException('Font story tidak ditemukan: ' . $path);
        }

        return $path;
    }

    protected function price($value, string $unitLabel): string
    {
        $value = (float) $value;
        if ($value <= 0) {
            return '';
        }

        return 'Rp ' . number_format($value, 0, ',', '.') . '/' . $unitLabel;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    protected function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
