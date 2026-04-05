<?php ob_start(); ?>
    <section class="auth">
        <h1>Регистрация</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

            <label>
                Имя
                <input type="text" name="name" required minlength="2" maxlength="100" value="<?= htmlspecialchars($name ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            </label>

            <label>
                Email
                <input type="email" name="email" required maxlength="255" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            </label>

            <label>
                Пароль
                <input type="password" name="password" required minlength="6" maxlength="100">
            </label>

            <button type="submit">Создать аккаунт</button>
        </form>

        <p><a href="/login">Уже есть аккаунт? Войти</a></p>
    </section>
<?php $content = ob_get_clean(); $title = 'Регистрация'; require __DIR__ . '/../layout.php'; ?>