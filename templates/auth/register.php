<?php
// templates/auth/register.php
ob_start();
?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <h1>ItisGram</h1>
                <p>Присоединяйся к сообществу</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="register-form" method="POST" action="/register">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="name">Имя</label>
                    <input type="text"
                           id="name"
                           name="name"
                           placeholder="Как вас зовут?"
                           value="<?= htmlspecialchars($name ?? '') ?>"
                           required
                           minlength="2"
                           maxlength="100"
                           autofocus>
                    <span class="error-message" id="name-error"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           placeholder="your@email.com"
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           required>
                    <span class="error-message" id="email-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Минимум 6 символов"
                           required
                           minlength="6">
                    <span class="error-message" id="password-error"></span>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Подтверждение пароля</label>
                    <input type="password"
                           id="password_confirm"
                           name="password_confirm"
                           placeholder="Повторите пароль"
                           required>
                    <span class="error-message" id="password-confirm-error"></span>
                </div>

                <button type="submit" id="submit-btn" class="btn">Создать аккаунт</button>
            </form>

            <div class="auth-footer">
                <p>Уже есть аккаунт?</p>
                <a href="/login">Войти</a>
            </div>
        </div>
    </div>
<?php
$content = ob_get_clean();
$title = 'Регистрация';
require __DIR__ . '/layout.php';
?>