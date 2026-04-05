<?php

namespace App\Services;

class FileUploadService
{
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;  // 10 MB
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024;  // 50 MB
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;   // 20 MB
    private const MAX_AVATAR_SIZE = 5 * 1024 * 1024;  // 5 MB

    private const ALLOWED_IMAGES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VIDEOS = ['video/mp4', 'video/webm', 'video/quicktime'];
    private const ALLOWED_FILES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    private const ALLOWED_AVATARS = ['image/jpeg', 'image/png', 'image/webp'];

    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
    }

    public function uploadAvatar(array $file): ?string
    {
        if (!$this->validateFile($file, 'avatar')) {
            return null;
        }

        $filename = $this->generateFileName($file['name']);
        $targetDir = $this->uploadDir . 'avatars/';

        $this->ensureDirectoryExists($targetDir);

        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '/uploads/avatars/' . $filename;
        }

        return null;
    }

    public function uploadMessageFile(array $file, string $type): ?string
    {
        if (!$this->validateFile($file, $type)) {
            return null;
        }

        $filename = $this->generateFileName($file['name']);

        $targetDir = match($type) {
            'image' => $this->uploadDir . 'images/',
            'video' => $this->uploadDir . 'videos/',
            default => $this->uploadDir . 'files/'
        };

        $this->ensureDirectoryExists($targetDir);

        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return '/uploads/' . $type . 's/' . $filename;
        }

        return null;
    }

    public function validateFile(array $file, string $type): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $maxSize = match($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'avatar' => self::MAX_AVATAR_SIZE,
            default => self::MAX_FILE_SIZE
        };

        if ($file['size'] > $maxSize) {
            return false;
        }

        $allowedMimes = match($type) {
            'image' => self::ALLOWED_IMAGES,
            'video' => self::ALLOWED_VIDEOS,
            'avatar' => self::ALLOWED_AVATARS,
            default => self::ALLOWED_FILES
        };

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        return in_array($mime, $allowedMimes, true);
    }

    public function generateFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid('file_', true) . '.' . $extension;
    }

    public function deleteFile(string $path): bool
    {
        $fullPath = __DIR__ . '/../../public' . $path;

        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    private function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}