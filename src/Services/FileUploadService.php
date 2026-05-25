<?php

namespace App\Services;

class FileUploadService
{
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024;
    private const MAX_AUDIO_SIZE = 20 * 1024 * 1024;
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const MAX_AVATAR_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_IMAGES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VIDEOS = ['video/mp4', 'video/webm', 'video/quicktime'];
    private const ALLOWED_AUDIO = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/aac', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm'];
    private const ALLOWED_FILES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];
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

        if ($this->moveFile($file['tmp_name'], $targetPath)) {
            return '/uploads/avatars/' . $filename;
        }

        return null;
    }

    public function uploadDialogueAvatar(array $file): ?string
    {
        if (!$this->validateFile($file, 'avatar')) {
            return null;
        }

        $filename = $this->generateFileName($file['name']);
        $targetDir = $this->uploadDir . 'dialogue-avatars/';

        $this->ensureDirectoryExists($targetDir);

        $targetPath = $targetDir . $filename;

        if ($this->moveFile($file['tmp_name'], $targetPath)) {
            return '/uploads/dialogue-avatars/' . $filename;
        }

        return null;
    }

    public function uploadMessageFile(array $file, string $type): ?string
    {
        if (!$this->validateFile($file, $type)) {
            return null;
        }

        $filename = $this->generateFileName($file['name']);

        $targetDir = match ($type) {
            'image' => $this->uploadDir . 'images/',
            'video' => $this->uploadDir . 'videos/',
            'audio' => $this->uploadDir . 'audios/',
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

        $maxSize = match ($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'audio' => self::MAX_AUDIO_SIZE,
            'avatar' => self::MAX_AVATAR_SIZE,
            default => self::MAX_FILE_SIZE
        };

        if ($file['size'] > $maxSize) {
            return false;
        }

        $allowedMimes = match ($type) {
            'image' => self::ALLOWED_IMAGES,
            'video' => self::ALLOWED_VIDEOS,
            'audio' => self::ALLOWED_AUDIO,
            'avatar' => self::ALLOWED_AVATARS,
            default => self::ALLOWED_FILES
        };

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;

        return in_array($mime, $allowedMimes, true);
    }

    public function generateFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid('file_', true) . '.' . $extension;
    }

    public function detectMessageFileType(array $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;

        if (is_string($mime)) {
            if (str_starts_with($mime, 'image/')) {
                return 'image';
            }

            if (str_starts_with($mime, 'video/')) {
                return 'video';
            }

            if (str_starts_with($mime, 'audio/') || in_array($mime, self::ALLOWED_AUDIO, true)) {
                return 'audio';
            }
        }

        return 'file';
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

    private function moveFile(string $sourcePath, string $targetPath): bool
    {
        if (is_uploaded_file($sourcePath)) {
            return move_uploaded_file($sourcePath, $targetPath);
        }

        if (file_exists($sourcePath)) {
            if (copy($sourcePath, $targetPath)) {
                @unlink($sourcePath);
                return true;
            }
        }

        return false;
    }
}
