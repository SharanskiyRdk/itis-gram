(function() {
    'use strict';

    
    const chatsPanel = document.getElementById('chats-panel');
    const resizer = document.getElementById('resizer');
    let isResizing = false;
    if (resizer) {
        resizer.addEventListener('mousedown', function(e) {
            isResizing = true;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', function(e) {
            if (!isResizing || !chatsPanel) return;

            let newWidth = e.clientX;
            if (newWidth < 260) newWidth = 260;
            if (newWidth > 400) newWidth = 400;

            chatsPanel.style.width = newWidth + 'px';
        });

        document.addEventListener('mouseup', function() {
            isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';

            if (chatsPanel) {
                localStorage.setItem('chatsPanelWidth', chatsPanel.offsetWidth);
            }
        });
    }

    
    const savedWidth = localStorage.getItem('chatsPanelWidth');
    if (savedWidth && chatsPanel) {
        chatsPanel.style.width = savedWidth + 'px';
    }

    let currentDialogueId = null;
    let refreshTimer = null;
    let friendsOnlyEnabled = false;
    let isRefreshing = false;
    let isRefreshingStatuses = false;
    let currentPollingChat = null;
    let sendingMessage = false;
    let pendingAttachment = null;

    
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    };

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').classList.remove('active');
        });
    });

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    
    window.toggleDropdown = function() {
        const dropdown = document.getElementById('chat-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    };

    
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('chat-dropdown');
        const dots = document.querySelector('.menu-dots');
        if (dropdown && dots && !dots.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            const chatId = this.dataset.chatId;
            if (chatId) {
                loadChat(chatId);
            }

            
            if (window.innerWidth <= 768) {
                document.getElementById('chats-panel')?.classList.remove('open');
            }
        });
    });

    async function loadChat(chatId) {
        try {
            currentDialogueId = chatId;
            const response = await fetch(`/chat/content?id=${chatId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();

            const chatArea = document.getElementById('chat-area');
            if (chatArea) {
                chatArea.innerHTML = html;
                initChatFeatures();
                syncMessagesMeta();
                refreshReadStatuses(chatId);
                startChatPolling(chatId);
            }
        } catch (error) {
            console.error('Error loading chat:', error);
        }
    }
    window.loadChat = loadChat;

    function startChatPolling(chatId) {
        if (currentPollingChat === chatId && refreshTimer) {
            return;
        }

        stopChatPolling();
        currentPollingChat = chatId;

        let stopped = false;
        async function poll() {
            if (stopped) return;
            await refreshChatMessages(chatId);
            if (stopped) return;
            refreshTimer = setTimeout(poll, 1000);
        }
        
        refreshTimer = setTimeout(poll, 1000);
        
        startChatPolling._stop = () => { stopped = true; clearTimeout(refreshTimer); refreshTimer = null; currentPollingChat = null; };
    }

    function stopChatPolling() {
        if (startChatPolling._stop) {
            startChatPolling._stop();
            startChatPolling._stop = null;
        } else if (refreshTimer) {
            clearTimeout(refreshTimer);
            refreshTimer = null;
        }
        currentPollingChat = null;
    }

    async function refreshChatMessages(chatId) {
        if (isRefreshing) return;
        isRefreshing = true;

        const messagesContainer = document.getElementById('messages-container');
        if (!messagesContainer || !chatId) {
            isRefreshing = false;
            return;
        }

        try {
            const messagesList = messagesContainer.querySelector('#messages-list');
            if (!messagesList) {
                isRefreshing = false;
                return;
            }

            const shouldStickToBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 140;
            const lastMessageId = messagesList.dataset.lastMessageId || '0';
            const lastMessageDate = messagesList.dataset.lastMessageDate || '';

            const response = await fetch(`/chat/content?id=${chatId}&partial=messages&after_id=${encodeURIComponent(lastMessageId)}&last_date=${encodeURIComponent(lastMessageDate)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();
            if (html.trim() === '') {
                await refreshReadStatuses(chatId);
                isRefreshing = false;
                return;
            }

            const messageInput = document.getElementById('message-input');
            const draft = messageInput ? messageInput.value : '';

            
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const newMessages = temp.querySelectorAll('.message');
            newMessages.forEach(msg => {
                const id = msg.dataset.messageId;
                if (!messagesList.querySelector(`.message[data-message-id="${id}"]`)) {
                    messagesList.insertAdjacentHTML('beforeend', msg.outerHTML);
                }
            });

            if (messageInput) messageInput.value = draft;
            syncMessagesMeta();
            await refreshReadStatuses(chatId);

            if (shouldStickToBottom) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            isRefreshing = false;
        } catch (error) {
            console.error('Error refreshing chat messages:', error);
            isRefreshing = false;
        }
    }

    function syncMessagesMeta() {
        const messagesList = document.getElementById('messages-list');
        if (!messagesList) return;

        const lastMessage = messagesList.querySelector('.message:last-of-type');
        if (!lastMessage) return;

        messagesList.dataset.lastMessageId = lastMessage.dataset.messageId || '';
        messagesList.dataset.lastMessageDate = lastMessage.dataset.messageDate || '';
    }

    function getCurrentDialogueId() {
        return document.querySelector('#chat-area input[name="dialogue_id"]')?.value
            || document.querySelector('input[name="dialogue_id"]')?.value
            || currentDialogueId
            || '';
    }

    function renderEmptyChatState() {
        return `
            <div class="empty-chat">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p>Выберите чат</p>
                <span>Нажмите на диалог, чтобы начать общение</span>
            </div>
        `;
    }

    function removeChatFromList(dialogueId) {
        const chatItem = document.querySelector(`.chat-item[data-chat-id="${dialogueId}"]`);
        const wasActive = chatItem?.classList.contains('active');

        if (chatItem) {
            chatItem.remove();
        }

        if (wasActive) {
            stopChatPolling();
            currentDialogueId = null;
            const chatArea = document.getElementById('chat-area');
            if (chatArea) {
                chatArea.innerHTML = renderEmptyChatState();
            }
        }
    }

    async function refreshReadStatuses(chatId) {
        if (!chatId) return;
        if (isRefreshingStatuses) return;
        isRefreshingStatuses = true;

        try {
            const response = await fetch(`/chat/read-status?dialogue_id=${encodeURIComponent(chatId)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();

            if (!result.success || !result.states) {
                isRefreshingStatuses = false;
                return;
            }

            Object.entries(result.states).forEach(([messageId, isRead]) => {
                const message = document.querySelector(`.message[data-message-id="${messageId}"]`);
                if (!message) return;

                const status = message.querySelector('.message-status');
                if (!status) return;

                const read = !!isRead;
                status.textContent = read ? '✓✓' : '✓';
                status.classList.toggle('message-status--read', read);
            });
        } catch (error) {
            console.error('Error refreshing read statuses:', error);
        } finally {
            isRefreshingStatuses = false;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatLastSeen(value) {
        if (!value) return 'Неизвестно';

        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatBytes(bytes) {
        if (!bytes || bytes < 1024) return `${bytes || 0} B`;
        const units = ['KB', 'MB', 'GB'];
        let size = bytes / 1024;
        let unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit += 1;
        }
        return `${size.toFixed(size >= 10 ? 0 : 1)} ${units[unit]}`;
    }

    function updateAttachmentPreview(file) {
        const preview = document.getElementById('attachment-preview');
        if (!preview) return;

        if (!file) {
            preview.hidden = true;
            preview.innerHTML = '';
            return;
        }

        preview.hidden = false;
        preview.innerHTML = `
            <span class="attachment-preview__name">${escapeHtml(file.name)}</span>
            <span class="attachment-preview__size">${formatBytes(file.size)}</span>
            <button type="button" class="attachment-preview__remove" aria-label="Удалить вложение">×</button>
        `;

        const removeBtn = preview.querySelector('.attachment-preview__remove');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const input = document.getElementById('message-attach-input');
                if (input) input.value = '';
                pendingAttachment = null;
                updateAttachmentPreview(null);
            });
        }
    }

    function applyChatFilters() {
        const query = document.getElementById('chats-search')?.value.toLowerCase().trim() || '';
        const items = document.querySelectorAll('.chat-item');

        items.forEach(item => {
            const name = item.querySelector('.chat-name span')?.textContent.toLowerCase() || '';
            const lastMsg = item.querySelector('.chat-last-message')?.textContent.toLowerCase() || '';
            const hasFriend = item.dataset.hasFriend === '1';
            const matchesQuery = query === '' || name.includes(query) || lastMsg.includes(query);
            const matchesFriend = !friendsOnlyEnabled || hasFriend;

            item.style.display = matchesQuery && matchesFriend ? 'flex' : 'none';
        });

        const friendsToggle = document.getElementById('friends-toggle');
        if (friendsToggle) {
            friendsToggle.classList.toggle('active', friendsOnlyEnabled);
        }
    }
    window.applyChatFilters = applyChatFilters;

    window.toggleFriendsOnly = function() {
        friendsOnlyEnabled = !friendsOnlyEnabled;
        applyChatFilters();
        toast.show(friendsOnlyEnabled ? 'Показаны только чаты с друзьями' : 'Фильтр друзей выключен', 'success');
    };

    window.openGroupInfo = function() {
        openModal('group-info-modal');
    };

    window.openGroupMembers = function() {
        openModal('group-info-modal');
    };

    window.startPrivateChat = async function(userId) {
        if (typeof window.startChat === 'function') {
            return window.startChat(userId);
        }

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
                closeModal('user-card-modal');
                closeModal('group-info-modal');
                window.location.href = `/?id=${result.dialogue_id}`;
            } else {
                toast.show(result.error || 'Ошибка', 'error');
            }
        } catch (error) {
            toast.show('Ошибка при создании чата', 'error');
        }
    };

    async function toggleFriendship(userId, button) {
        try {
            const formData = new FormData();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            formData.append('user_id', userId);
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('/profile/friend-toggle', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();
            if (result.success) {
                button.textContent = result.is_friend ? 'Удалить из друзей' : 'Добавить в друзья';
                button.dataset.friendState = result.is_friend ? '1' : '0';
                toast.show(result.message || 'Статус дружбы обновлён', 'success');
                applyChatFilters();
            } else {
                toast.show(result.message || result.error || 'Не удалось обновить друзей', 'error');
            }
        } catch (error) {
            toast.show('Не удалось обновить друзей', 'error');
        }
    }

    window.openUserCard = async function(userId) {
        if (!userId || Number(userId) === Number(window.currentUserId)) {
            return;
        }

        const modal = document.getElementById('user-card-modal');
        const content = document.getElementById('user-card-modal-content');
        if (!modal || !content) return;

        modal.classList.add('active');
        content.querySelector('.modal-body').innerHTML = '<div class="user-card-loading">Загрузка...</div>';

        try {
            const response = await fetch(`/profile/card?user_id=${encodeURIComponent(userId)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();

            if (!result.success || !result.card) {
                content.querySelector('.modal-body').innerHTML = '<div class="user-card-loading">Пользователь не найден</div>';
                return;
            }

            const card = result.card;
            const user = card.user || {};
            const friendButtonText = card.is_friend ? 'Удалить из друзей' : 'Добавить в друзья';

            content.querySelector('.modal-body').innerHTML = `
                <div class="user-card">
                    <div class="user-card__avatar" onclick="closeModal('user-card-modal')">
                        ${user.avatar ? `<img src="${escapeHtml(user.avatar)}" alt="${escapeHtml(user.name)}">` : `<div class="avatar-placeholder" style="background:#667eea">${escapeHtml((user.name || '?').charAt(0))}</div>`}
                    </div>
                    <div class="user-card__name">${escapeHtml(user.name || '')}</div>
                    <div class="user-card__email">${escapeHtml(user.email || '')}</div>
                    <div class="user-card__meta">${user.student_group ? `Группа: ${escapeHtml(user.student_group)}` : 'Группа не указана'}</div>
                    <div class="user-card__meta">${user.is_online ? 'Онлайн' : `Был(а) ${formatLastSeen(user.last_seen)}`}</div>
                    ${user.bio ? `<div class="user-card__bio">${escapeHtml(user.bio)}</div>` : ''}
                    <div class="user-card__actions">
                        <button type="button" class="btn" data-friend-state="${card.is_friend ? '1' : '0'}">${friendButtonText}</button>
                        <button type="button" class="btn btn--secondary">Написать</button>
                    </div>
                </div>
            `;

            const buttons = content.querySelectorAll('.user-card__actions .btn');
            const friendButton = buttons[0];
            const messageButton = buttons[1];
            if (friendButton && card.can_toggle_friend) {
                friendButton.addEventListener('click', () => toggleFriendship(user.id, friendButton));
            } else if (friendButton) {
                friendButton.disabled = true;
            }
            if (messageButton) {
                messageButton.addEventListener('click', () => window.startPrivateChat(user.id));
            }
        } catch (error) {
            content.querySelector('.modal-body').innerHTML = '<div class="user-card-loading">Не удалось загрузить профиль</div>';
        }
    };

    window.goToProfile = function(userId) {
        window.openUserCard(userId);
    };

    function initChatFeatures() {
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const attachBtn = document.getElementById('attach-btn');
        const attachInput = document.getElementById('message-attach-input');
        const messagesContainer = document.getElementById('messages-container');
        const groupSettingsForm = document.getElementById('group-settings-form');

        if (messageInput && sendBtn) {
            sendBtn.dataset.bound = '1';

            if (!messageInput.dataset.bound) {
                messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });

                
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                    try {
                        const dlg = document.querySelector('input[name="dialogue_id"]')?.value || currentDialogueId;
                        if (dlg) localStorage.setItem('draft_' + dlg, this.value);
                    } catch (e) {}
                });

                
                try {
                    const dlg = document.querySelector('input[name="dialogue_id"]')?.value || currentDialogueId;
                    if (dlg) {
                        const draft = localStorage.getItem('draft_' + dlg);
                        if (draft) messageInput.value = draft;
                    }
                } catch (e) {}

                messageInput.dataset.bound = '1';
            }
        }

        if (attachBtn && attachInput && !attachBtn.dataset.bound) {
            attachBtn.addEventListener('click', () => attachInput.click());
            attachBtn.dataset.bound = '1';
        }

        if (attachInput && !attachInput.dataset.bound) {
            attachInput.addEventListener('change', function() {
                pendingAttachment = this.files && this.files.length > 0 ? this.files[0] : null;
                updateAttachmentPreview(pendingAttachment);
            });
            attachInput.dataset.bound = '1';
        }

        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        if (groupSettingsForm && !groupSettingsForm.dataset.bound) {
            groupSettingsForm.dataset.bound = 'true';
            groupSettingsForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(groupSettingsForm);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('/chat/group/update', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();

                    if (result.success) {
                        toast.show('Группа обновлена', 'success');
                        closeModal('group-info-modal');
                        if (currentDialogueId) {
                            loadChat(currentDialogueId);
                        }
                    } else {
                        toast.show(result.error || 'Не удалось обновить группу', 'error');
                    }
                } catch (error) {
                    toast.show('Не удалось обновить группу', 'error');
                }
            });
        }
    }

    async function sendMessage() {
        if (sendingMessage) return;

        const messageInput = document.getElementById('message-input');
        const chatInputArea = document.querySelector('.chat-input-area');
        if (chatInputArea?.dataset.blocked === '1') {
            toast.show('Чат заблокирован', 'error');
            return;
        }
        const dialogueId = getCurrentDialogueId();
        const content = messageInput?.value.trim();

        if ((!content && !pendingAttachment) || !dialogueId) return;

        sendingMessage = true;

        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) sendBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('dialogue_id', dialogueId);
            formData.append('content', content);
            if (pendingAttachment) {
                formData.append('attachment', pendingAttachment);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('/chat/send', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const result = await response.json();

            if (result.success && messageInput) {
                messageInput.value = '';
                messageInput.style.height = 'auto';
                pendingAttachment = null;
                updateAttachmentPreview(null);
                try {
                    if (dialogueId) localStorage.removeItem('draft_' + dialogueId);
                } catch (e) {}
                
                const messagesList = document.getElementById('messages-list');
                const messagesContainer = document.getElementById('messages-container');
                if (result.message_html) {
                    if (messagesList) {
                        const temp = document.createElement('div');
                        temp.innerHTML = result.message_html;
                        const newMessages = temp.querySelectorAll('.message');
                        newMessages.forEach(msg => {
                            const id = msg.dataset.messageId;
                            if (!messagesList.querySelector(`.message[data-message-id="${id}"]`)) {
                                messagesList.insertAdjacentHTML('beforeend', msg.outerHTML);
                            }
                        });
                        syncMessagesMeta();
                        if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    } else {
                        
                        loadChat(dialogueId);
                    }
                } else {
                    
                    if (!messagesList) {
                        loadChat(dialogueId);
                    }
                }
            } else {
                toast.show(result.error || 'Ошибка при отправке', 'error');
            }
        } catch (error) {
            toast.show('Ошибка при отправке', 'error');
        } finally {
            sendingMessage = false;
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    window.sendChatMessage = sendMessage;

    
    async function postChatAction(url, payload, successMessage) {
        const formData = new FormData();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        Object.entries(payload).forEach(([key, value]) => {
            formData.append(key, value);
        });

        if (csrfToken) formData.append('csrf_token', csrfToken);

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Не удалось выполнить действие');
        }

        toast.show(successMessage, 'success');
        document.getElementById('chat-dropdown')?.classList.remove('active');
        return result;
    }

    window.clearChat = async function(scope = 'me') {
        const confirmMessage = scope === 'all'
            ? 'Очистить чат у всех участников?'
            : 'Очистить чат только у себя?';

        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            await postChatAction('/chat/clear', {
                dialogue_id: getCurrentDialogueId(),
                scope
            }, scope === 'all' ? 'Чат очищен у всех' : 'Чат очищен у вас');

            const dialogueId = getCurrentDialogueId();
            if (scope === 'all') {
                removeChatFromList(dialogueId);
            } else if (currentDialogueId) {
                loadChat(currentDialogueId);
            }
        } catch (error) {
            toast.show(error.message || 'Не удалось очистить чат', 'error');
        }
    };

    window.blockUser = async function() {
        if (!confirm('Заблокировать пользователя? Вы не сможете отправлять ему сообщения.')) {
            return;
        }

        try {
            await postChatAction('/chat/block', {
                dialogue_id: getCurrentDialogueId()
            }, 'Пользователь заблокирован');
            if (currentDialogueId) {
                loadChat(currentDialogueId);
            }
        } catch (error) {
            toast.show(error.message || 'Не удалось заблокировать пользователя', 'error');
        }
    };

    window.blockAndClear = async function() {
        if (!confirm('Заблокировать пользователя и очистить чат у всех?')) {
            return;
        }

        try {
            await postChatAction('/chat/block-clear', {
                dialogue_id: getCurrentDialogueId()
            }, 'Пользователь заблокирован, чат очищен');
            removeChatFromList(getCurrentDialogueId());
        } catch (error) {
            toast.show(error.message || 'Не удалось заблокировать и очистить чат', 'error');
        }
    };

    window.unblockUser = async function() {
        if (!confirm('Разблокировать пользователя?')) {
            return;
        }

        try {
            await postChatAction('/chat/unblock', {
                dialogue_id: getCurrentDialogueId()
            }, 'Пользователь разблокирован');
            if (currentDialogueId) {
                loadChat(currentDialogueId);
            }
        } catch (error) {
            toast.show(error.message || 'Не удалось разблокировать пользователя', 'error');
        }
    };

    window.openGroupSettings = function() {
        openModal('group-info-modal');
    };

    
    window.toast = {
        show: function(message, type = 'success') {
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) existingToast.remove();

            const toast = document.createElement('div');
            toast.className = `toast-message toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === 'error' ? '#dc3545' : '#28a745'};
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 14px;
                z-index: 10000;
                animation: fadeInUp 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    };

    
    const chatsSearch = document.getElementById('chats-search');
    if (chatsSearch) {
        chatsSearch.addEventListener('input', function() {
            applyChatFilters();
        });
        applyChatFilters();
    }

    
    (function loadFromUrl() {
        try {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');
            if (id) {
                const el = document.querySelector(`.chat-item[data-chat-id="${id}"]`);
                if (el) {
                    document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
                    el.classList.add('active');
                    loadChat(id);
                    
                    el.scrollIntoView({ block: 'nearest', behavior: 'auto' });
                } else {
                    
                    loadChat(id);
                }
            }

            window.addEventListener('beforeunload', stopChatPolling);
        } catch (e) {
            console.error('Error parsing URL id:', e);
        }
    })();
})();