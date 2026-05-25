<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= csrf_meta() ?>
    <title><?= htmlspecialchars($title ?? 'Admin', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/toast.css">
</head>
<body class="admin-page">
    <div class="admin-shell">
        <aside class="admin-nav">
            <div class="admin-brand">ItisGram Admin</div>
            <nav>
                <a href="/admin">Dashboard</a>
                <a href="/admin/users">Users</a>
                <a href="/admin/tickets">Tickets</a>
            </nav>
        </aside>

        <main class="admin-content">
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="/js/toast.js"></script>
    <script>
        window.csrfToken = '<?= csrf_token() ?>';
        <?php if (isset($_SESSION['flash_success'])): ?>
        toast.show('<?= addslashes($_SESSION['flash_success']) ?>', 'success');
        <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
        toast.show('<?= addslashes($_SESSION['flash_error']) ?>', 'error');
        <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
