<?php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'ItisGram') ?></title>
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
<?= $content ?? '' ?>
<script src="/js/auth.js"></script>
</body>
</html>