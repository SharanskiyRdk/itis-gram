<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrf_meta() ?>
    <title><?= htmlspecialchars($title ?? 'ItisGram', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<header class="header">
    <div class="container header__inner">
        <a class="logo" href="/">ItisGram</a>

        <nav class="nav">
            <a href="/">Чаты</a>
            <a href="/profile">Профиль</a>
            <a href="/search/users">Поиск</a>
        </nav>

        <form method="POST" action="/logout" class="logout-form">
            <?= csrf_field() ?> <!-- Использовать helper -->
            <button type="submit">Выйти</button>
        </form>
    </div>
</header>

<main class="container">
    <?= $content ?? '' ?>
</main>

<script>
    window.csrfToken = '<?= csrf_token() ?>';
    window.currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
    window.currentUserName = <?= json_encode($_SESSION['user_name'] ?? null) ?>;
    window.currentUserEmail = <?= json_encode($_SESSION['user_email'] ?? null) ?>;
</script>

<script>
    // Автоматическое обновление CSRF токена для всех AJAX запросов
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        // Добавляем CSRF токен в заголовки для POST запросов
        if (args[1] && args[1].method && args[1].method.toUpperCase() === 'POST') {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                if (!args[1].headers) {
                    args[1].headers = {};
                }
                args[1].headers['X-CSRF-TOKEN'] = csrfToken;

                // Если есть FormData, добавляем токен в него
                if (args[1].body instanceof FormData && !args[1].body.has('csrf_token')) {
                    args[1].body.append('csrf_token', csrfToken);
                }
            }
        }
        return originalFetch.apply(this, args);
    };
</script>

<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= htmlspecialchars($script, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>