<?php
// templates/chat/index.php
ob_start();

// Получаем данные пользователя для меню
$user = $_SESSION;
?>
    <div class="app">
        <!-- Левая панель меню -->
        <div class="sidebar-menu">
            <div class="menu-top">
                <div class="menu-avatar" id="menu-avatar" onclick="openModal('profile-modal')">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="background: #667eea">
                            <?= mb_substr($user['user_name'] ?? 'U', 0, 1) ?>
                        </div>
                    <?php endif; ?>
                    <div class="online-dot"></div>
                </div>

                <div class="menu-item" onclick="openModal('new-chat-modal')" title="Новый чат">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </div>

                <div class="menu-item" onclick="openModal('friends-modal')" title="Друзья">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>

                <div class="menu-item" onclick="openModal('favorites-modal')" title="Избранное">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
            </div>

            <div class="menu-bottom">
                <div class="menu-item" onclick="openModal('settings-modal')" title="Настройки">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </div>

                <form method="POST" action="/logout" id="logout-form" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="menu-item" style="background: none; border: none; width: 48px; cursor: pointer;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Список чатов -->
        <div class="chats-panel" id="chats-panel">
            <div class="chats-header">
                <h2>Чаты</h2>
                <div class="search-wrapper">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" placeholder="Поиск" id="chats-search">
                </div>
            </div>

            <div class="chats-list" id="chats-list">
                <?php if (empty($dialogues)): ?>
                    <div style="text-align: center; padding: 40px; color: #b0b3b8;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <p style="margin-top: 16px;">Нет диалогов</p>
                        <span style="font-size: 14px;">Начните общение с другом</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($dialogues as $dialogue): ?>
                        <div class="chat-item" data-chat-id="<?= $dialogue['id'] ?>">
                            <div class="chat-avatar">
                                <?php if ($dialogue['type'] === 'group'): ?>
                                    <div class="avatar-placeholder group-avatar">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    </div>
                                <?php else:
                                    // Для личного чата пытаемся получить имя собеседника
                                    $otherUserName = $dialogue['title'] ?? 'Чат';
                                    ?>
                                    <div class="avatar-placeholder" style="background: #667eea">
                                        <?= mb_substr($otherUserName, 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="chat-info">
                                <div class="chat-name">
                                    <span><?= htmlspecialchars($dialogue['title'] ?? ($otherUserName ?? 'Личный чат')) ?></span>
                                    <?php if (!empty($dialogue['last_message_time'])): ?>
                                        <span class="chat-time"><?= date('H:i', strtotime($dialogue['last_message_time'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-last-message">
                                    <?= htmlspecialchars(mb_substr($dialogue['last_message'] ?? 'Новый чат', 0, 50)) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resizer -->
        <div class="resizer" id="resizer"></div>

        <!-- Область чата -->
        <div class="chat-area" id="chat-area">
            <div class="empty-chat">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p>Выберите чат</p>
                <span>Нажмите на диалог, чтобы начать общение</span>
            </div>
        </div>
    </div>

    <!-- Модальное окно профиля -->
    <div id="profile-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Профиль</h3>
                <div class="modal-close">&times;</div>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 80px; height: 80px; margin: 0 auto; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;">
                        <?= mb_substr($user['user_name'] ?? 'U', 0, 1) ?>
                    </div>
                    <h4 style="margin-top: 12px;"><?= htmlspecialchars($user['user_name'] ?? '') ?></h4>
                    <p style="color: #b0b3b8; font-size: 14px;"><?= htmlspecialchars($user['user_email'] ?? '') ?></p>
                </div>
                <a href="/profile" class="btn" style="display: block; text-align: center; text-decoration: none; margin-bottom: 10px;">Редактировать профиль</a>
            </div>
        </div>
    </div>

    <!-- Модальное окно создания чата -->
    <div id="new-chat-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Начать чат</h3>
                <div class="modal-close">&times;</div>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="tab-btn active" data-tab="user">Новый чат</button>
                    <button class="tab-btn" data-tab="invite">Пригласить</button>
                </div>
                <div id="user-tab" class="tab-content active">
                    <input type="text" id="user-search" placeholder="Поиск пользователей..." class="search-input" style="width: 100%; padding: 12px; border-radius: 12px; border: none; background: #363740; color: white;">
                    <div id="user-search-results" style="margin-top: 16px;"></div>
                </div>
                <div id="invite-tab" class="tab-content" style="display: none;">
                    <p style="margin-bottom: 16px;">Поделитесь ссылкой-приглашением:</p>
                    <input type="text" id="invite-link" readonly value="<?= ($_SERVER['HTTPS'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/register" style="width: 100%; padding: 12px; border-radius: 12px; border: none; background: #363740; color: white;">
                    <button class="btn" style="margin-top: 12px;" onclick="copyInviteLink()">Копировать ссылку</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно настроек -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Настройки</h3>
                <div class="modal-close">&times;</div>
            </div>
            <div class="modal-body">
                <div class="settings-item" style="cursor: pointer; margin-bottom: 8px;" onclick="window.location.href='/profile'">
                    <span>✏️ Редактировать профиль</span>
                </div>
                <div class="settings-item" style="margin-bottom: 8px;">
                    <span>🔔 Уведомления и звуки</span>
                </div>
                <div class="settings-item" style="margin-bottom: 8px;">
                    <span>🔒 Конфиденциальность</span>
                </div>
                <div class="settings-item" style="margin-bottom: 8px;">
                    <span>🌐 Язык</span>
                </div>
                <div class="settings-item">
                    <span>❓ Связаться с поддержкой</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .tab-btn {
            padding: 8px 16px;
            background: #363740;
            border: none;
            border-radius: 20px;
            color: #b0b3b8;
            cursor: pointer;
        }
        .tab-btn.active {
            background: #667eea;
            color: white;
        }
        .settings-item {
            padding: 12px;
            border-radius: 12px;
            background: #363740;
            transition: background 0.2s;
            cursor: pointer;
        }
        .settings-item:hover {
            background: #3e4049;
        }
        .search-input:focus {
            outline: none;
            border: 1px solid #667eea;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #5a67d8;
        }
    </style>

    <script>
        function copyInviteLink() {
            const input = document.getElementById('invite-link');
            input.select();
            document.execCommand('copy');
            toast.show('Ссылка скопирована!', 'success');
        }

        // Поиск пользователей
        const userSearch = document.getElementById('user-search');
        if (userSearch) {
            let searchTimeout;
            userSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length < 2) {
                    document.getElementById('user-search-results').innerHTML = '';
                    return;
                }
                searchTimeout = setTimeout(() => searchUsers(query), 300);
            });
        }

        async function searchUsers(query) {
            const resultsDiv = document.getElementById('user-search-results');
            try {
                const response = await fetch(`/search/users?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.users && result.users.length) {
                    resultsDiv.innerHTML = result.users.map(user => `
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 12px; cursor: pointer; transition: background 0.2s;"
                         onclick="startChat(${user.id})"
                         onmouseover="this.style.background='#3e4049'"
                         onmouseout="this.style.background='transparent'">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white;">
                            ${escapeHtml(user.name.charAt(0))}
                        </div>
                        <div>
                            <div><strong>${escapeHtml(user.name)}</strong></div>
                            <div style="font-size: 12px; color: #b0b3b8;">${escapeHtml(user.email)}</div>
                        </div>
                    </div>
                `).join('');
                } else {
                    resultsDiv.innerHTML = '<p style="text-align: center; color: #b0b3b8;">Пользователи не найдены</p>';
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }

        async function startChat(userId) {
            try {
                const formData = new FormData();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                formData.append('user_id', userId);
                if (csrfToken) formData.append('csrf_token', csrfToken);

                const response = await fetch('/search/users/chat', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();
                if (result.success) {
                    closeModal('new-chat-modal');
                    window.location.href = `/chat?id=${result.dialogue_id}`;
                } else {
                    toast.show(result.error || 'Ошибка', 'error');
                }
            } catch (error) {
                toast.show('Ошибка при создании чата', 'error');
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // Поиск по чатам
        const chatsSearch = document.getElementById('chats-search');
        if (chatsSearch) {
            chatsSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                document.querySelectorAll('.chat-item').forEach(item => {
                    const name = item.querySelector('.chat-name span')?.textContent.toLowerCase() || '';
                    const lastMsg = item.querySelector('.chat-last-message')?.textContent.toLowerCase() || '';
                    item.style.display = name.includes(query) || lastMsg.includes(query) ? 'flex' : 'none';
                });
            });
        }
    </script>

<?php
$content = ob_get_clean();
$title = 'ItisGram';
// Используем новый layout без header
require __DIR__ . '/layout.php';
?>