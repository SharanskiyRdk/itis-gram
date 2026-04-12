<?php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Страница не найдена</title>
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
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-code">404</div>
    <div class="error-title">Страница не найдена</div>
    <div class="error-message"><?= htmlspecialchars($errorMessage ?? 'Запрашиваемая страница не существует') ?></div>
    <a href="/" class="btn">Вернуться на главную</a>
</div>
</body>
</html>