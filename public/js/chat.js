(function() {
    'use strict';

    // Resize панели
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

    // Восстановление ширины панели
    const savedWidth = localStorage.getItem('chatsPanelWidth');
    if (savedWidth && chatsPanel) {
        chatsPanel.style.width = savedWidth + 'px';
    }

    // Модальные окна
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

    // Выпадающее меню в шапке чата
    window.toggleDropdown = function() {
        const dropdown = document.getElementById('chat-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    };

    // Закрыть dropdown при клике вне
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('chat-dropdown');
        const dots = document.querySelector('.menu-dots');
        if (dropdown && dots && !dots.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    // Выбор чата
    document.querySelectorAll('.chat-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            const chatId = this.dataset.chatId;
            if (chatId) {
                loadChat(chatId);
            }

            // На мобильных закрываем панель
            if (window.innerWidth <= 768) {
                document.getElementById('chats-panel')?.classList.remove('open');
            }
        });
    });

    async function loadChat(chatId) {
        try {
            const response = await fetch(`/chat/content?id=${chatId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();

            const chatArea = document.getElementById('chat-area');
            if (chatArea) {
                chatArea.innerHTML = html;
                initChatFeatures();
            }
        } catch (error) {
            console.error('Error loading chat:', error);
        }
    }

    function initChatFeatures() {
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const messagesContainer = document.getElementById('messages-container');

        if (messageInput && sendBtn) {
            sendBtn.addEventListener('click', () => sendMessage());
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Auto-resize textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    async function sendMessage() {
        const messageInput = document.getElementById('message-input');
        const dialogueId = document.querySelector('input[name="dialogue_id"]')?.value;
        const content = messageInput?.value.trim();

        if (!content || !dialogueId) return;

        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) sendBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('dialogue_id', dialogueId);
            formData.append('content', content);

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
                loadChat(dialogueId);
            } else {
                toast.show(result.error || 'Ошибка при отправке', 'error');
            }
        } catch (error) {
            toast.show('Ошибка при отправке', 'error');
        } finally {
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    // Действия с чатом
    window.clearChat = function() {
        if (confirm('Очистить всю историю сообщений?')) {
            toast.show('Чат очищен', 'success');
            document.getElementById('chat-dropdown')?.classList.remove('active');
        }
    };

    window.blockUser = function() {
        if (confirm('Заблокировать пользователя? Вы не сможете отправлять ему сообщения.')) {
            toast.show('Пользователь заблокирован', 'success');
            document.getElementById('chat-dropdown')?.classList.remove('active');
        }
    };

    window.blockAndClear = function() {
        if (confirm('Заблокировать пользователя и очистить чат?')) {
            toast.show('Пользователь заблокирован, чат очищен', 'success');
            document.getElementById('chat-dropdown')?.classList.remove('active');
        }
    };

    window.goToProfile = function(userId) {
        window.location.href = `/profile?id=${userId}`;
    };

    // Toast уведомления
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
})();