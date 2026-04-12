<?php

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Ошибка сервера</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container { text-align: center; max-width: 500px; }
        .error-code { font-size: 120px; font-weight: 800; color: rgba(255,255,255,0.3); margin-bottom: 20px; }
        .error-title { font-size: 28px; color: white; margin-bottom: 16px; }
        .error-message { color: rgba(255,255,255,0.8); margin-bottom: 32px; }
        .btn { display: inline-block; padding: 12px 24px; background: white; color: #667eea; text-decoration: none; border-radius: 12px; font-weight: 600; }
        .btn:hover { transform: translateY(-2px); }
        .error-debug { background: rgba(0,0,0,0.3); border-radius: 12px; padding: 16px; text-align: left; font-family: monospace; font-size: 12px; color: #ffcc00; margin-top: 20px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-code">500</div>
    <div class="error-title">Внутренняя ошибка сервера</div>
    <div class="error-message"><?= htmlspecialchars($errorMessage ?? 'Что-то пошло не так. Попробуйте позже.') ?></div>
    <a href="/" class="btn">Вернуться на главную</a>

    <?php if ($debugMode && !empty($errorContext)): ?>
        <div class="error-debug">
            <strong>Debug Information:</strong><br><br>
            <?php foreach ($errorContext as $key => $value): ?>
                <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars((string)$value) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>