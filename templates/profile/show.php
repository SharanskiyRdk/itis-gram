<?php ob_start(); ?>
    <section class="profile">
        <h1>Профиль</h1>

        <div class="profile-card">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Аватар" width="120" height="120">
            <?php else: ?>
                <img src="/images/avatar-placeholder.png" alt="Аватар" width="120" height="120">
            <?php endif; ?>

            <div>
                <p><strong>Имя:</strong> <?= htmlspecialchars($user['name'] ?? 'Пользователь', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <p><strong>О себе:</strong> <?= nl2br(htmlspecialchars($user['bio'] ?? 'Участник ItisGram', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></p>
                <p><strong>Статус:</strong> <?= $user['is_online'] ? 'Онлайн' : 'Был(а) ' . date('d.m.Y H:i', strtotime($user['last_seen'])) ?></p>
                <p><strong>Дата регистрации:</strong> <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>

        <form method="POST" action="/profile/avatar" enctype="multipart/form-data" class="form" id="avatar-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
            <button type="submit">Загрузить аватар</button>
        </form>

        <button id="edit-profile-btn" class="button">Редактировать профиль</button>
    </section>

    <script>
        document.getElementById('avatar-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);

            try {
                const response = await fetch('/profile/avatar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Аватар успешно обновлен');
                    location.reload();
                } else {
                    alert(result.error || 'Ошибка при загрузке');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при загрузке');
            }
        });

        document.getElementById('edit-profile-btn')?.addEventListener('click', () => {
            // Модальное окно для редактирования или редирект
            const name = prompt('Введите новое имя:', '<?= htmlspecialchars($user['name'] ?? '') ?>');
            if (name && name.trim()) {
                updateProfile(name, null);
            }
        });

        async function updateProfile(name, bio) {
            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrf_token ?>');
            formData.append('name', name);
            if (bio) formData.append('bio', bio);

            try {
                const response = await fetch('/profile/update', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Профиль обновлен');
                    location.reload();
                } else {
                    alert(result.error || 'Ошибка при обновлении');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при обновлении');
            }
        }
    </script>
<?php $content = ob_get_clean(); $title = 'Профиль'; require __DIR__ . '/../layout.php'; ?>