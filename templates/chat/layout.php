<?php
// templates/chat/layout.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <?= csrf_meta() ?>
    <title><?= htmlspecialchars($title ?? 'ItisGram') ?></title>
    <link rel="stylesheet" href="/css/chat.css">
</head>
<body>
<?= $content ?? '' ?>
<script src="/js/chat.js"></script>
</body>
</html>