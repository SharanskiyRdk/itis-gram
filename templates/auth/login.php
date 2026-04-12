<?php
// templates/auth/login.php
ob_start();
?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <h1>ItisGram</h1>
                <p>Связь с одногруппниками</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="login-form" method="POST" action="/login">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           placeholder="your@email.com"
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           required
                           autocomplete="email"
                           autofocus>
                    <span class="error-message" id="email-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="••••••"
                           required
                           autocomplete="current-password">
                    <span class="error-message" id="password-error"></span>
                </div>

                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="remember">
                        Запомнить меня
                    </label>
                    <a href="/forgot-password" class="forgot-link">Забыли пароль?</a>
                </div>

                <button type="submit" id="submit-btn" class="btn">Войти</button>
            </form>

            <div class="auth-footer">
                <p>Нет аккаунта?</p>
                <a href="/register">Создать аккаунт</a>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
<?php
$content = ob_get_clean();
$title = 'Вход';
require __DIR__ . '/layout.php';
?>