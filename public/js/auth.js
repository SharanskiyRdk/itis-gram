(function() {
    'use strict';

    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('submit-btn');

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                validateEmail(this);
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                validatePassword(this);
            });
        }

        loginForm.addEventListener('submit', function(e) {
            let isValid = true;

            if (emailInput && !validateEmail(emailInput)) {
                isValid = false;
            }

            if (passwordInput && !validatePassword(passwordInput)) {
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                showToast('Пожалуйста, исправьте ошибки в форме', 'error');
            } else if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Вход...';
            }
        });
    }

    if (registerForm) {
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirm');
        const submitBtn = document.getElementById('submit-btn');

        if (nameInput) {
            nameInput.addEventListener('input', function() {
                validateName(this);
            });
        }

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                validateEmail(this);
            });
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                validatePassword(this);
                if (confirmPasswordInput) {
                    validateConfirmPassword(confirmPasswordInput, passwordInput);
                }
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                validateConfirmPassword(this, passwordInput);
            });
        }

        registerForm.addEventListener('submit', function(e) {
            let isValid = true;

            if (nameInput && !validateName(nameInput)) isValid = false;
            if (emailInput && !validateEmail(emailInput)) isValid = false;
            if (passwordInput && !validatePassword(passwordInput)) isValid = false;
            if (confirmPasswordInput && !validateConfirmPassword(confirmPasswordInput, passwordInput)) isValid = false;

            if (!isValid) {
                e.preventDefault();
                showToast('Пожалуйста, исправьте ошибки в форме', 'error');
            } else if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Регистрация...';
            }
        });
    }

    function validateName(input) {
        const value = input.value.trim();
        const errorSpan = document.getElementById('name-error');

        if (value.length < 2) {
            showError(input, errorSpan, 'Имя должно содержать минимум 2 символа');
            return false;
        }

        if (value.length > 100) {
            showError(input, errorSpan, 'Имя не должно превышать 100 символов');
            return false;
        }

        clearError(input, errorSpan);
        return true;
    }

    function validateEmail(input) {
        const value = input.value.trim();
        const errorSpan = document.getElementById('email-error');
        const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;

        if (!value) {
            showError(input, errorSpan, 'Email обязателен');
            return false;
        }

        if (!emailRegex.test(value)) {
            showError(input, errorSpan, 'Введите корректный email адрес');
            return false;
        }

        clearError(input, errorSpan);
        return true;
    }

    function validatePassword(input) {
        const value = input.value;
        const errorSpan = document.getElementById('password-error');

        if (!value) {
            showError(input, errorSpan, 'Пароль обязателен');
            return false;
        }

        if (value.length < 6) {
            showError(input, errorSpan, 'Пароль должен содержать минимум 6 символов');
            return false;
        }

        clearError(input, errorSpan);
        return true;
    }

    function validateConfirmPassword(input, passwordInput) {
        const value = input.value;
        const password = passwordInput ? passwordInput.value : '';
        const errorSpan = document.getElementById('password-confirm-error');

        if (value !== password) {
            showError(input, errorSpan, 'Пароли не совпадают');
            return false;
        }

        clearError(input, errorSpan);
        return true;
    }

    function showError(input, errorSpan, message) {
        input.classList.add('input-error');
        if (errorSpan) {
            errorSpan.textContent = message;
        }
    }

    function clearError(input, errorSpan) {
        input.classList.remove('input-error');
        if (errorSpan) {
            errorSpan.textContent = '';
        }
    }

    function showToast(message, type) {
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast-message toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
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

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
})();