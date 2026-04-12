// public/js/profile.js
(function() {
    'use strict';

    // Модальные окна
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    }

    window.openModal = openModal;
    window.closeModal = closeModal;

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

    const editProfileBtn = document.getElementById('edit-profile-btn');
    const editProfileForm = document.getElementById('edit-profile-form');

    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', () => openModal('edit-profile-modal'));
    }

    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editProfileForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('/profile/update', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    toast.show('Профиль обновлён', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toast.show(result.error, 'error');
                }
            } catch (error) {
                toast.show('Ошибка при обновлении', 'error');
            }
        });
    }

    const avatarInput = document.getElementById('avatar-input');
    const avatarContainer = document.getElementById('profile-avatar');

    if (avatarContainer) {
        avatarContainer.addEventListener('click', () => {
            if (avatarInput) avatarInput.click();
        });
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('/profile/avatar', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    toast.show('Аватар обновлён', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toast.show(result.error, 'error');
                }
            } catch (error) {
                toast.show('Ошибка при загрузке', 'error');
            }
        });
    }

    const verifyBtn = document.getElementById('request-verify-btn');
    const verifyForm = document.getElementById('verify-form');

    if (verifyBtn) {
        verifyBtn.addEventListener('click', () => openModal('verify-modal'));
    }

    if (verifyForm) {
        verifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const group = document.getElementById('student-group').value;
            if (!group.trim()) {
                toast.show('Введите номер группы', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('student_group', group);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('/profile/verify-request', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    toast.show('Запрос отправлен! Администратор рассмотрит его', 'success');
                    closeModal('verify-modal');
                } else {
                    toast.show(result.error, 'error');
                }
            } catch (error) {
                toast.show('Ошибка при отправке', 'error');
            }
        });
    }

    const supportBtn = document.getElementById('support-btn');
    const ticketForm = document.getElementById('ticket-form');

    if (supportBtn) {
        supportBtn.addEventListener('click', () => openModal('support-modal'));
    }

    if (ticketForm) {
        ticketForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const subject = document.getElementById('ticket-subject').value;
            const message = document.getElementById('ticket-message').value;

            if (!subject || !message) {
                toast.show('Заполните все поля', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('subject', subject);
            formData.append('message', message);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('/profile/support', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.success) {
                    toast.show('Обращение отправлено', 'success');
                    closeModal('support-modal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toast.show(result.error, 'error');
                }
            } catch (error) {
                toast.show('Ошибка при отправке', 'error');
            }
        });
    }

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
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    };
})();