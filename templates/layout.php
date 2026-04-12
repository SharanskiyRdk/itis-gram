<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrf_meta() ?>
    <title><?= htmlspecialchars($title ?? 'ItisGram', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/toast.css">
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
            <?= csrf_field() ?>
            <button type="submit">Выйти</button>
        </form>
    </div>
</header>

<main class="container">
    <?= $content ?? '' ?>
</main>

<script src="/js/toast.js"></script>

<script>
    window.csrfToken = '<?= csrf_token() ?>';
    window.currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
    window.currentUserName = <?= json_encode($_SESSION['user_name'] ?? null) ?>;
    window.currentUserEmail = <?= json_encode($_SESSION['user_email'] ?? null) ?>;
</script>

<script>
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(async (response) => {
            const clonedResponse = response.clone();
            try {
                const data = await clonedResponse.json();
                if (data && data.toast) {
                    toast.show(data.toast.message, data.toast.type);
                }
            } catch (e) {
                // Не JSON ответ, игнорируем
            }
            return response;
        });
    };

    // Перехват alert для замены на toast (опционально)
    const originalAlert = window.alert;
    window.alert = function(message) {
        toast.show(message, 'info');
    };
</script>

<script>
    // Уведомление о последнем входе, если есть
    <?php if (isset($_SESSION['last_login_notification'])): ?>
    toast.show('<?= addslashes($_SESSION['last_login_notification']) ?>', 'info');
    <?php unset($_SESSION['last_login_notification']); ?>
    <?php endif; ?>
</script>

<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= htmlspecialchars($script, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>