<?php ob_start(); ?>
    <section class="search">
        <h1>Поиск пользователей</h1>

        <form method="GET" action="/search/users" class="form" id="search-form">
            <input type="text" name="q" placeholder="Имя или email" maxlength="100"
                   value="<?= htmlspecialchars($q ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                   id="search-input" autocomplete="off">
            <button type="submit">Найти</button>
        </form>

        <div class="search-results" id="search-results">
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <article class="user-card" data-user-id="<?= $user['id'] ?>">
                        <div class="user-card__avatar">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар" width="50" height="50">
                            <?php else: ?>
                                <img src="/images/avatar-placeholder.png" alt="Аватар" width="50" height="50">
                            <?php endif; ?>
                        </div>
                        <div class="user-card__info">
                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                            <p><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div class="user-card__status">
                            <span class="status-badge <?= $user['is_online'] ? 'online' : 'offline' ?>">
                                <?= $user['is_online'] ? 'Онлайн' : 'Офлайн' ?>
                            </span>
                        </div>
                        <div class="user-card__action">
                            <button class="button button--small start-chat-btn" data-user-id="<?= $user['id'] ?>">
                                Написать
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php elseif ($q !== ''): ?>
                <p class="search-empty">Пользователи не найдены</p>
            <?php endif; ?>
        </div>
    </section>

    <style>
        .user-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        .user-card:hover {
            background: #f9f9f9;
        }
        .user-card__avatar img {
            border-radius: 50%;
            object-fit: cover;
        }
        .user-card__info {
            flex: 1;
        }
        .user-card__info strong {
            display: block;
            margin-bottom: 4px;
        }
        .user-card__info p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .status-badge.online {
            background: #10b98120;
            color: #10b981;
        }
        .status-badge.offline {
            background: #6b728020;
            color: #6b7280;
        }
        .button--small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        .search-empty {
            text-align: center;
            color: #999;
            padding: 2rem;
        }
        .loading {
            text-align: center;
            padding: 2rem;
            color: #667eea;
        }
        @media (max-width: 768px) {
            .user-card {
                flex-wrap: wrap;
            }
            .user-card__action {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;

            // Реактивный поиск
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();

                    if (query.length === 0) {
                        searchResults.innerHTML = '';
                        return;
                    }

                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                });
            }

            async function performSearch(query) {
                searchResults.innerHTML = '<div class="loading">Поиск...</div>';

                try {
                    const response = await fetch(`/search/users?q=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();
                    renderSearchResults(result.users);
                } catch (error) {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<p class="search-empty">Ошибка при поиске</p>';
                }
            }

            function renderSearchResults(users) {
                if (!users || users.length === 0) {
                    searchResults.innerHTML = '<p class="search-empty">Пользователи не найдены</p>';
                    return;
                }

                searchResults.innerHTML = users.map(user => `
                    <article class="user-card" data-user-id="${user.id}">
                        <div class="user-card__avatar">
                            ${user.avatar
                    ? `<img src="${escapeHtml(user.avatar)}" alt="Аватар" width="50" height="50">`
                    : `<img src="/images/avatar-placeholder.png" alt="Аватар" width="50" height="50">`
                }
                        </div>
                        <div class="user-card__info">
                            <strong>${escapeHtml(user.name)}</strong>
                            <p>${escapeHtml(user.email)}</p>
                        </div>
                        <div class="user-card__status">
                            <span class="status-badge ${user.is_online ? 'online' : 'offline'}">
                                ${user.is_online ? 'Онлайн' : 'Офлайн'}
                            </span>
                        </div>
                        <div class="user-card__action">
                            <button class="button button--small start-chat-btn" data-user-id="${user.id}">
                                Написать
                            </button>
                        </div>
                    </article>
                `).join('');

                // Привязываем обработчики для кнопок
                document.querySelectorAll('.start-chat-btn').forEach(btn => {
                    btn.addEventListener('click', startChat);
                });
            }

            async function startChat(event) {
                const btn = event.currentTarget;
                const userId = btn.dataset.userId;

                btn.disabled = true;
                btn.textContent = 'Создание...';

                try {
                    const formData = new FormData();
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    formData.append('user_id', userId);
                    if (csrfToken) formData.append('csrf_token', csrfToken);

                    const response = await fetch('/search/users/chat', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.location.href = `/chat?id=${result.dialogue_id}`;
                    } else {
                        toast.show(result.error || 'Не удалось создать чат', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Написать';
                    }
                } catch (error) {
                    console.error('Chat creation error:', error);
                    toast.show('Ошибка при создании чата', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Написать';
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

            document.querySelectorAll('.start-chat-btn').forEach(btn => {
                btn.addEventListener('click', startChat);
            });
        });
    </script>
<?php $content = ob_get_clean(); $title = 'Поиск'; require __DIR__ . '/../layout.php'; ?>