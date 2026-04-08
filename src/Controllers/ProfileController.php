<?php

namespace App\Controllers;

use App\Services\ProfileService;
use JetBrains\PhpStorm\NoReturn;

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

    #[NoReturn]
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

    #[NoReturn]
    public function avatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            $this->json(['error' => 'Файл не передан'], 400);
            return;
        }

        $file = $_FILES['avatar'];

        // Загружаем аватар через сервис
        $avatarPath = $this->profileService->updateAvatar($this->currentUserId(), $file);

        if ($avatarPath) {
            $this->json(['success' => true, 'avatar_url' => $avatarPath]);
        } else {
            $this->json(['error' => 'Не удалось загрузить аватар. Проверьте формат и размер файла (макс. 5MB, JPEG, PNG, WebP)'], 422);
        }
    }

    #[NoReturn]
    public function deleteAvatar(): void
    {
        $this->verifyCsrf();
        $this->requireAuth();

        $result = $this->profileService->deleteAvatar($this->currentUserId());

        if ($result) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Не удалось удалить аватар'], 500);
        }
    }
}