<?php

namespace App\Controllers;

use App\Services\ProfileService;

class ProfileController extends AbstractController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    public function show(): void
    {
        $this->requireAuth();

        $user = $this->profileService->getUser($this->currentUserId());

        $this->render('profile/show', [
            'user' => $user
        ]);
    }

    public function edit(): void
    {
        $this->requireAuth();

        $user = $this->profileService->getUser($this->currentUserId());
        $this->render('profile/edit', ['user' => $user]);
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $name = trim((string)($_POST['name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100) {
            $this->json(['error' => 'Некорректное имя'], 422);
            return;
        }

        if (mb_strlen($bio) > 500) {
            $this->json(['error' => 'Слишком длинное описание'], 422);
            return;
        }

        $result = $this->profileService->updateProfile(
            $this->currentUserId(),
            $name,
            $bio
        );

        if ($result) {
            $_SESSION['user_name'] = $name;
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось обновить профиль'], 500);
        }
    }

    public function avatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            $this->json(['error' => 'Файл не передан'], 400);
            return;
        }

        $file = $_FILES['avatar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Ошибка загрузки файла'], 400);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['error' => 'Аватар слишком большой (макс. 5MB)'], 422);
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            $this->json(['error' => 'Недопустимый формат. Используйте JPEG, PNG или WebP'], 422);
            return;
        }

        $result = $this->profileService->updateAvatar(
            $this->currentUserId(),
            $file
        );

        if ($result) {
            $this->json(['success' => true, 'avatar_url' => $result]);
        } else {
            $this->json(['error' => 'Не удалось загрузить аватар'], 500);
        }
    }
}