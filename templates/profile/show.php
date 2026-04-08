<?php ob_start(); ?>
    <section class="profile">
        <h1>Профиль</h1>

        <div class="profile-card">
            <div class="profile-avatar" id="profile-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                         alt="Аватар"
                         width="120"
                         height="120"
                         id="avatar-img">
                <?php else: ?>
                    <img src="/images/avatar-placeholder.png"
                         alt="Аватар"
                         width="120"
                         height="120"
                         id="avatar-img">
                <?php endif; ?>
                <div class="avatar-overlay" id="avatar-overlay">
                    <span>Изменить</span>
                </div>
            </div>

            <div class="profile-info">
                <p><strong>Имя:</strong> <span id="user-name"><?= htmlspecialchars($user['name'] ?? 'Пользователь', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <p><strong>О себе:</strong> <?= nl2br(htmlspecialchars($user['bio'] ?? 'Участник ItisGram', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></p>
                <p><strong>Статус:</strong> <?= $user['is_online'] ? 'Онлайн' : 'Был(а) ' . date('d.m.Y H:i', strtotime($user['last_seen'])) ?></p>
                <p><strong>Дата регистрации:</strong> <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>

        <!-- Скрытая форма для загрузки аватара -->
        <form method="POST" action="/profile/avatar" enctype="multipart/form-data" class="form" id="avatar-form" style="display: none;">
            <?= csrf_field() ?>
            <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp">
        </form>

        <div class="profile-actions">
            <button id="edit-profile-btn" class="button">Редактировать профиль</button>
        </div>

        <!-- Модальное окно для редактирования профиля -->
        <div id="edit-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h2>Редактировать профиль</h2>
                <form id="edit-profile-form" class="form">
                    <?= csrf_field() ?>
                    <label>
                        Имя:
                        <input type="text" name="name" id="edit-name" required minlength="2" maxlength="100">
                    </label>
                    <label>
                        О себе:
                        <textarea name="bio" id="edit-bio" rows="4" maxlength="500"></textarea>
                    </label>
                    <button type="submit">Сохранить</button>
                </form>
            </div>
        </div>

        <!-- Модальное окно для управления аватаром -->
        <div id="avatar-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h2>Управление аватаром</h2>
                <div class="avatar-modal-preview">
                    <img id="modal-avatar-img" src="" alt="Аватар" width="150" height="150">
                </div>
                <div class="avatar-modal-buttons">
                    <button id="upload-avatar-btn" class="button">Загрузить новое фото</button>
                    <?php if (!empty($user['avatar'])): ?>
                        <button id="delete-avatar-btn" class="button button--danger">Удалить аватар</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <style>
        .profile-avatar {
            position: relative;
            cursor: pointer;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
            font-size: 14px;
        }

        .profile-avatar:hover .avatar-overlay {
            opacity: 1;
        }

        .profile-avatar img {
            border-radius: 50%;
            object-fit: cover;
            width: 120px;
            height: 120px;
        }

        .profile-info {
            flex: 1;
        }

        .profile-actions {
            margin-top: 1rem;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }

        .avatar-modal-preview {
            text-align: center;
            margin: 1rem 0;
        }

        .avatar-modal-preview img {
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .button--danger {
            background: #dc3545;
        }

        .button--danger:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .profile-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .avatar-modal-buttons {
                flex-direction: column;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Элементы
            const avatarContainer = document.getElementById('profile-avatar');
            const avatarForm = document.getElementById('avatar-form');
            const avatarInput = document.getElementById('avatar-input');
            const avatarModal = document.getElementById('avatar-modal');
            const editModal = document.getElementById('edit-modal');
            const modalAvatarImg = document.getElementById('modal-avatar-img');
            const avatarImg = document.getElementById('avatar-img');

            // Открытие модального окна аватара
            if (avatarContainer) {
                avatarContainer.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const currentAvatarSrc = avatarImg ? avatarImg.src : '';
                    if (modalAvatarImg) {
                        modalAvatarImg.src = currentAvatarSrc;
                    }
                    avatarModal.style.display = 'flex';
                });
            }

            // Закрытие модальных окон
            document.querySelectorAll('.modal-close').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });

            // Закрытие по клику вне окна
            window.addEventListener('click', function(e) {
                if (e.target === avatarModal) {
                    avatarModal.style.display = 'none';
                }
                if (e.target === editModal) {
                    editModal.style.display = 'none';
                }
            });

            // Загрузка нового аватара
            document.getElementById('upload-avatar-btn')?.addEventListener('click', function() {
                avatarInput.click();
                avatarModal.style.display = 'none';
            });

            // Обработка выбора файла
            avatarInput?.addEventListener('change', async function(e) {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('avatar', this.files[0]);

                    // Добавляем CSRF токен
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (csrfToken) {
                        formData.append('csrf_token', csrfToken);
                    }

                    try {
                        const response = await fetch('/profile/avatar', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Обновляем аватар на странице
                            const timestamp = new Date().getTime();
                            if (avatarImg) {
                                avatarImg.src = result.avatar_url + '?t=' + timestamp;
                            }
                            alert('Аватар успешно обновлен');
                            location.reload(); // Перезагружаем для обновления всех данных
                        } else {
                            alert(result.error || 'Ошибка при загрузке аватара');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Ошибка при загрузке аватара');
                    }
                }
            });

            // Удаление аватара
            document.getElementById('delete-avatar-btn')?.addEventListener('click', async function() {
                if (confirm('Вы уверены, что хотите удалить аватар?')) {
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        const formData = new FormData();
                        formData.append('csrf_token', csrfToken);

                        const response = await fetch('/profile/avatar/delete', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Аватар удален');
                            location.reload();
                        } else {
                            alert(result.error || 'Ошибка при удалении аватара');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Ошибка при удалении аватара');
                    }
                }
            });

            // Редактирование профиля
            document.getElementById('edit-profile-btn')?.addEventListener('click', function() {
                const userName = document.getElementById('user-name')?.textContent || '';
                const userBio = document.querySelector('.profile-info p:nth-child(3)')?.textContent.replace('О себе:', '').trim() || '';

                document.getElementById('edit-name').value = userName;
                document.getElementById('edit-bio').value = userBio;
                editModal.style.display = 'flex';
            });

            // Сохранение профиля
            document.getElementById('edit-profile-form')?.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                try {
                    const response = await fetch('/profile/update', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Профиль обновлен');
                        location.reload();
                    } else {
                        alert(result.error || 'Ошибка при обновлении профиля');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ошибка при обновлении профиля');
                }
            });
        });
    </script>
<?php
$content = ob_get_clean();
$title = 'Профиль';
require __DIR__ . '/../layout.php';
?>