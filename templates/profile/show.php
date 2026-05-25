<?php
// templates/profile/show.php
ob_start();

// Защита от неопределённых переменных
$user = $user ?? null;
$tickets = $tickets ?? [];
$verificationStatus = $verificationStatus ?? ['is_verified' => false, 'student_group' => null];
$stats = $stats ?? ['messages' => 0, 'dialogues' => 0];
$studentGroup = $studentGroup ?? ($verificationStatus['student_group'] ?? null);
$isAdmin = $isAdmin ?? false;
$adminStats = $adminStats ?? null;

?>
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar" id="profile-avatar">
                    <?php if ($user && $user->getAvatar()): ?>
                        <img src="<?= htmlspecialchars($user->getAvatar()) ?>" alt="Аватар" id="avatar-img">
                    <?php else: ?>
                        <img src="/images/avatar-placeholder.svg" alt="Аватар" id="avatar-img">
                    <?php endif; ?>
                    <div class="avatar-overlay">
                        <span>Изменить</span>
                    </div>
                    <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>

                <div class="profile-info">
                    <h2><?= htmlspecialchars($user ? $user->getName() : 'Пользователь') ?></h2>
                    <div class="email"><?= htmlspecialchars($user ? $user->getEmail() : '') ?></div>

                    <?php if (!empty($verificationStatus['is_verified'])): ?>
                        <div class="verified-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#2e7d32">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <span>Подтверждённый студент ИТИС</span>
                            <?php if (!empty($verificationStatus['student_group'])): ?>
                                <span style="margin-left: 8px; background: #c8e6c9; padding: 2px 8px; border-radius: 12px;"><?= htmlspecialchars($verificationStatus['student_group']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="unverified-badge" id="request-verify-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="#e65100">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                            <span>Подтвердить статус студента ИТИС</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($user && $user->getBio()): ?>
                        <div class="bio"><?= nl2br(htmlspecialchars($user->getBio())) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($studentGroup)): ?>
                        <div class="group-chip">
                            <span class="group-chip__label">Академическая группа</span>
                            <strong><?= htmlspecialchars($studentGroup) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="value"><?= (int)($stats['messages'] ?? 0) ?></div>
                    <div class="label">Сообщений</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= (int)($stats['dialogues'] ?? 0) ?></div>
                    <div class="label">Диалогов</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= $user ? date('d.m.Y', strtotime($user->getCreatedAt())) : date('d.m.Y') ?></div>
                    <div class="label">Дата регистрации</div>
                </div>
            </div>

            <div class="profile-actions">
                <button class="profile-action profile-action--primary" id="edit-profile-btn">✏️ Редактировать</button>
                <button class="profile-action" id="support-btn">💬 Поддержка</button>
                <button class="profile-action profile-action--ghost" id="settings-btn">⚙️ Настройки</button>
                <?php if ($isAdmin): ?>
                    <a href="/admin" class="profile-action profile-action--admin">🛠 Панель администратора</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin && !empty($adminStats)): ?>
            <div class="admin-panel-head">
                <p class="admin-panel-kicker">ADMIN INFO</p>
            </div>

            <div class="admin-panel-stats">
                <div class="admin-panel-stat">
                    <strong><?= (int)$adminStats['users'] ?></strong>
                    <span>Пользователи</span>
                </div>

                <div class="admin-panel-stat">
                    <strong><?= (int)$adminStats['tickets_open'] ?></strong>
                    <span>Открытые тикеты</span>
                </div>

                <div class="admin-panel-stat">
                    <strong><?= (int)$adminStats['groups'] ?></strong>
                    <span>Групповые чаты</span>
                </div>
            </div>

            <div class="admin-panel-actions">
                <a href="/admin/users" class="profile-mini-link">
                    👥 Пользователи
                </a>

                <a href="/admin/tickets" class="profile-mini-link">
                    🎫 Обращения
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($tickets)): ?>
            <div class="profile-card">
                <h3 style="margin-bottom: 16px;">Мои обращения</h3>
                <div class="tickets-list">
                    <?php foreach ($tickets as $ticket): ?>
                                    <div class="ticket-item" data-ticket-id="<?= htmlspecialchars($ticket['id']) ?>" data-admin-response="<?= htmlspecialchars($ticket['admin_response'] ?? '') ?>" data-status="<?= htmlspecialchars($ticket['status'] ?? 'open') ?>">
                            <div class="ticket-header">
                                <span class="ticket-subject"><?= htmlspecialchars($ticket['subject'] ?? '') ?></span>
                                <span class="ticket-status status-<?= htmlspecialchars($ticket['status'] ?? 'open') ?>">
                                <?php
                                $status = $ticket['status'] ?? 'open';
                                if ($status === 'open') echo 'Открыто';
                                elseif ($status === 'in_progress') echo 'В обработке';
                                elseif ($status === 'resolved') echo 'Решено';
                                else echo 'Закрыто';
                                ?>
                            </span>
                            </div>
                            <div class="ticket-message"><?= nl2br(htmlspecialchars(mb_substr($ticket['message'] ?? '', 0, 100))) ?>...</div>
                            <div class="ticket-date" style="font-size: 12px; color: #65676b;">
                                <?= date('d.m.Y H:i', strtotime($ticket['created_at'] ?? 'now')) ?>
                            </div>
                            <?php if (!empty($ticket['admin_response'])): ?>
                                <div class="ticket-response" style="display:none">
                                    <strong>Ответ администратора:</strong><br>
                                    <?= nl2br(htmlspecialchars($ticket['admin_response'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно редактирования профиля -->
    <div id="edit-profile-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Редактировать профиль</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="edit-profile-form">
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user ? $user->getName() : '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>О себе</label>
                        <textarea name="bio" rows="4" placeholder="Расскажите о себе..."><?= htmlspecialchars($user && $user->getBio() ? $user->getBio() : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Сохранить</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно верификации -->
    <div id="verify-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Подтверждение статуса студента ИТИС</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px;">Для подтверждения статуса студента ИТИС укажите номер вашей группы. Администратор проверит информацию и подтвердит её.</p>
                <form id="verify-form">
                    <div class="form-group">
                        <label>Номер группы (например: 11-405)</label>
                        <input type="text" id="student-group" placeholder="XX-XXX" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Отправить запрос</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно поддержки -->
    <div id="support-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Служба поддержки</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px;">Опишите вашу проблему или вопрос. Мы ответим вам в ближайшее время.</p>
                <form id="ticket-form">
                    <div class="form-group">
                        <label>Тема</label>
                        <input type="text" id="ticket-subject" placeholder="Кратко опишите суть" required>
                    </div>
                    <div class="form-group">
                        <label>Сообщение</label>
                        <textarea id="ticket-message" rows="5" placeholder="Подробно опишите вашу проблему..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Отправить</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно настроек -->
    <div id="settings-modal" class="modal">
        <div class="modal-content modal-content--wide">
            <div class="modal-header">
                <h3>Настройки профиля</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="settings-grid">
                    <button class="settings-card" data-settings-action="profile">
                        <span class="settings-card__icon">✏️</span>
                        <span class="settings-card__title">Профиль</span>
                        <span class="settings-card__text">Имя, аватар и описание</span>
                    </button>
                    <button class="settings-card" data-settings-action="notifications">
                        <span class="settings-card__icon">🔔</span>
                        <span class="settings-card__title">Уведомления</span>
                        <span class="settings-card__text">Тихие часы и звуки</span>
                    </button>
                    <button class="settings-card" data-settings-action="privacy">
                        <span class="settings-card__icon">🔒</span>
                        <span class="settings-card__title">Приватность</span>
                        <span class="settings-card__text">Кто видит профиль</span>
                    </button>
                    <button class="settings-card" data-settings-action="support">
                        <span class="settings-card__icon">💬</span>
                        <span class="settings-card__title">Поддержка</span>
                        <span class="settings-card__text">Создать обращение</span>
                    </button>
                </div>
                <div class="settings-note">
                    Дополнительные настройки откроем следующим шагом и привяжем к реальным действиям.
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="/css/profile.css">
    <script src="/js/profile.js"></script>
    <script>
        // Show ticket response in modal when clicking a ticket
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.ticket-item').forEach(function(el) {
                el.addEventListener('click', function() {
                    const resp = el.dataset.adminResponse || '';
                    const status = el.dataset.status || 'open';
                    if (resp.trim() !== '') {
                        // reuse support modal to show response
                        const modal = document.createElement('div');
                        modal.className = 'modal active';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <div class="modal-header"><h3>Ответ по обращению</h3><span class="modal-close">&times;</span></div>
                                <div class="modal-body"><p>${resp.replace(/\n/g, '<br>')}</p><p style="margin-top:12px;font-size:13px;color:#666">Статус: ${status}</p></div>
                            </div>`;
                        document.body.appendChild(modal);
                        modal.querySelector('.modal-close').addEventListener('click', function(){ modal.remove(); });
                        modal.addEventListener('click', function(e){ if (e.target === modal) modal.remove(); });
                    }
                });
            });
        });
    </script>

<?php
$content = ob_get_clean();
$title = 'Профиль';
require __DIR__ . '/../layout.php';
?>