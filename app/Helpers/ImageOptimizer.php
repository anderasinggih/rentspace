<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageOptimizer
{
    /**
     * Compress and save uploaded image to WebP (or optimized JPEG) with max dimensions and quality.
     * Keeps file sizes extremely small (~30KB - 80KB) for fast server storage and low bandwidth.
     *
     * @param UploadedFile $file
     * @param string $subDir Folder inside public uploads, e.g. 'bukti_bayar', 'bukti_ambil'
     * @param int $maxWidth Max width in pixels (default: 1080)
     * @param int $maxHeight Max height in pixels (default: 1080)
     * @param int $quality Compression quality 1-100 (default: 72)
     * @return string Filename that was saved
     */
    public static function compressAndSave(
        UploadedFile $file,
        string $subDir = 'bukti',
        int $maxWidth = 1080,
        int $maxHeight = 1080,
        int $quality = 72
    ): string {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? public_path();
        if (!is_dir($root) || !is_writable($root)) {
            $root = public_path();
        }

        $targetDir = rtrim((string) $root, '/') . '/uploads/' . trim($subDir, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Also ensure public_path has the folder if DOCUMENT_ROOT is different
        $fallbackDir = public_path('uploads/' . trim($subDir, '/'));
        if (!is_dir($fallbackDir) && $fallbackDir !== $targetDir) {
            @mkdir($fallbackDir, 0755, true);
        }

        $sourcePath = $file->getRealPath();
        $sourceImage = null;

        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: ''));

        // Load image resource via GD
        if (function_exists('imagecreatefromstring')) {
            $fileData = @file_get_contents($sourcePath);
            if ($fileData !== false) {
                $sourceImage = @imagecreatefromstring($fileData);
            }
        }

        if (!$sourceImage) {
            if ($ext === 'png' || str_contains($mime, 'png')) {
                $sourceImage = @imagecreatefrompng($sourcePath);
            } elseif ($ext === 'webp' || str_contains($mime, 'webp')) {
                $sourceImage = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null;
            } else {
                $sourceImage = @imagecreatefromjpeg($sourcePath);
            }
        }

        // If GD fails for some unusual format, fallback to standard move
        if (!$sourceImage) {
            $safeExt = $ext ?: 'jpg';
            $filename = Str::slug($subDir) . '-' . time() . '-' . Str::random(6) . '.' . $safeExt;
            $file->move($targetDir, $filename);
            return $filename;
        }

        // Handle EXIF orientation (mobile phone camera photos often have EXIF rotation)
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($sourcePath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $sourceImage = imagerotate($sourceImage, 180, 0);
                            break;
                        case 6:
                            $sourceImage = imagerotate($sourceImage, -90, 0);
                            break;
                        case 8:
                            $sourceImage = imagerotate($sourceImage, 90, 0);
                            break;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore exif errors
            }
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate resize dimensions while maintaining aspect ratio
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1.0);
        $newWidth = max(1, (int) round($origWidth * $ratio));
        $newHeight = max(1, (int) round($origHeight * $ratio));

        $targetCanvas = imagecreatetruecolor($newWidth, $newHeight);

        // Fill background white in case of transparent PNG/WebP converted to JPEG/WebP
        $white = imagecolorallocate($targetCanvas, 255, 255, 255);
        imagefilledrectangle($targetCanvas, 0, 0, $newWidth, $newHeight, $white);

        // Resample with smooth bicubic interpolation
        imagecopyresampled(
            $targetCanvas,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        $supportsWebp = function_exists('imagewebp');
        $outputExt = $supportsWebp ? 'webp' : 'jpg';
        $filename = Str::slug($subDir) . '-' . time() . '-' . Str::random(8) . '.' . $outputExt;
        $destPath = $targetDir . '/' . $filename;

        if ($supportsWebp) {
            imagewebp($targetCanvas, $destPath, $quality);
        } else {
            imagejpeg($targetCanvas, $destPath, $quality);
        }

        // Mirror to fallback if separate
        if ($targetDir !== $fallbackDir && is_dir($fallbackDir)) {
            @copy($destPath, $fallbackDir . '/' . $filename);
        }

        imagedestroy($sourceImage);
        imagedestroy($targetCanvas);

        return $filename;
    }
}
