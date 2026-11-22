<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Ponnostack Platform - High-Performance File & Image Uploader
 * 
 * Provides:
 * - MIME-type validation using finfo
 * - Automatic image resizing & compression to ~200KB WebP
 * - Safe randomized multi-tenant file naming
 * - Cross-domain full URL generation for client storefronts
 */
class Uploader
{
    public const IMAGE_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
    ];

    /**
     * Get Base Platform Root URL
     */
    public static function getBaseUrl(): string
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            $protocol = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $baseDir = dirname($script);

            // Strip nested /admin, /backend, /api, /portal, /public, /storefront folders to reach project root
            $baseDir = preg_replace('#/(api|backend|admin|public|portal|storefront)(/.*)?$#i', '', $baseDir);
            $cleanBase = ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') ? '' : rtrim($baseDir, '/');

            return "{$protocol}://{$host}{$cleanBase}";
        }

        return 'http://localhost';
    }

    /**
     * Upload an incoming file safely (Logos, general images)
     *
     * @param array $file Single $_FILES entry
     * @param string $destinationDir Subdirectory inside root (e.g. 'uploads/logos')
     * @param array $allowedTypes Map of ext => mime
     * @param int $maxSizeBytes Max file size in bytes (default 5MB)
     * @return array
     */
    public static function upload(
        array $file,
        string $destinationDir = 'uploads/logos',
        array $allowedTypes = self::IMAGE_TYPES,
        int $maxSizeBytes = 5242880
    ): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'অবৈধ ফাইল আপলোড প্যারামিটার।'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'কোনো ফাইল নির্বাচন করা হয়নি।'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'ফাইলের সাইজ অনুমোদিত সর্বোচ্চ সীমার চেয়ে বড়।'];
            default:
                return ['success' => false, 'error' => 'ফাইল আপলোডে ত্রুটি ঘটেছে (কোড: ' . $file['error'] . ')।'];
        }

        if ($file['size'] > $maxSizeBytes) {
            $maxMb = round($maxSizeBytes / (1024 * 1024), 1);
            return ['success' => false, 'error' => "ফাইলের সাইজ {$maxMb}MB এর চেয়ে ছোট হতে হবে।"];
        }

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === 'svg' && ($realMime === 'image/svg+xml' || $realMime === 'text/plain' || $realMime === 'text/xml')) {
            $realMime = 'image/svg+xml';
        }

        if (!array_key_exists($ext, $allowedTypes) || !in_array($realMime, $allowedTypes, true)) {
            return ['success' => false, 'error' => 'অবৈধ ফাইল ফরম্যাট। শুধুমাত্র PNG, JPG, JPEG, WEBP অথবা SVG ফাইল গ্রহণযোগ্য।'];
        }

        // Target Directory
        $projectRoot = dirname(__DIR__, 2);
        $cleanDir = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $destinationDir), DIRECTORY_SEPARATOR);
        $absoluteTargetDir = $projectRoot . DIRECTORY_SEPARATOR . $cleanDir;

        if (!is_dir($absoluteTargetDir)) {
            if (!mkdir($absoluteTargetDir, 0755, true) && !is_dir($absoluteTargetDir)) {
                return ['success' => false, 'error' => 'ফাইল সেভ করার ডিরেক্টরি তৈরি করা যায়নি।'];
            }
        }

        // If SVG or GIF, move directly without re-compression
        if ($ext === 'svg' || $ext === 'gif') {
            $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
            $destinationPath = $absoluteTargetDir . DIRECTORY_SEPARATOR . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                return ['success' => false, 'error' => 'ফাইল স্থানান্তর ব্যর্থ হয়েছে।'];
            }

            $relativePath = str_replace('\\', '/', $cleanDir) . '/' . $safeName;
            return [
                'success'       => true,
                'filename'      => $safeName,
                'relative_path' => $relativePath,
                'url'           => '/' . $relativePath,
                'full_url'      => self::getBaseUrl() . '/' . $relativePath,
            ];
        }

        // For JPG, PNG, WEBP: compress & convert to modern WebP
        $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.webp';
        $destinationPath = $absoluteTargetDir . DIRECTORY_SEPARATOR . $safeName;

        $compressed = self::compressImage($file['tmp_name'], $destinationPath, 204800, 1200);
        if (!$compressed) {
            // Fallback: move uploaded file directly with original extension
            $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
            $destinationPath = $absoluteTargetDir . DIRECTORY_SEPARATOR . $safeName;
            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                return ['success' => false, 'error' => 'ফাইল আপলোড ব্যর্থ হয়েছে।'];
            }
        }

        $relativePath = str_replace('\\', '/', $cleanDir) . '/' . $safeName;
        return [
            'success'       => true,
            'filename'      => $safeName,
            'relative_path' => $relativePath,
            'url'           => '/' . $relativePath,
            'full_url'      => self::getBaseUrl() . '/' . $relativePath,
        ];
    }

    /**
     * Compress and scale image down to target size & max dimension
     */
    public static function compressImage(
        string $sourcePath,
        string $destinationPath,
        int $targetSizeBytes = 204800,
        int $maxDimension = 1200
    ): bool {
        if (!extension_loaded('gd')) {
            return @copy($sourcePath, $destinationPath);
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return @copy($sourcePath, $destinationPath);
        }

        $mime = $info['mime'];
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            default      => null,
        };

        if (!$src) {
            return @copy($sourcePath, $destinationPath);
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        $scale = 1.0;
        if ($origW > $maxDimension || $origH > $maxDimension) {
            $scale = min($maxDimension / $origW, $maxDimension / $origH);
        }

        $newW = max(1, (int)round($origW * $scale));
        $newH = max(1, (int)round($origH * $scale));

        $dest = imagecreatetruecolor($newW, $newH);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dest, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $useWebp = function_exists('imagewebp');
        $qualities = [82, 75, 65, 55];
        $saved = false;

        foreach ($qualities as $q) {
            if ($useWebp) {
                $saved = @imagewebp($dest, $destinationPath, $q);
            } else {
                $saved = @imagejpeg($dest, $destinationPath, $q);
            }

            clearstatcache(true, $destinationPath);
            if ($saved && file_exists($destinationPath) && filesize($destinationPath) <= $targetSizeBytes) {
                break;
            }
        }

        imagedestroy($dest);
        return $saved && file_exists($destinationPath);
    }
}
