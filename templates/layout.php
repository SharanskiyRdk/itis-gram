<?php /** @var string $title */ ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ItisGram', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/app.css">
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <button type="submit">Выйти</button>
        </form>
    </div>
</header>

<main class="container">
    <?= $content ?? '' ?>
</main>
</body>
</html>