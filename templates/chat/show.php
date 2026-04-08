<?php ob_start(); ?>
    <section class="chat">
        <h1><?= htmlspecialchars($dialogue['title'] ?? 'Чат', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

        <div class="messages" id="messages-container">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <article class="message <?= $message['user_id'] == currentUserId() ? 'message--mine' : '' ?>">
                        <div class="message__meta">
                            <?= htmlspecialchars($message['user_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ·
                            <?= date('H:i', strtotime($message['created_at'])) ?>
                        </div>
                        <div class="message__body">
                            <?= nl2br(htmlspecialchars($message['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="messages__empty">Нет сообщений. Напишите что-нибудь!</p>
            <?php endif; ?>
        </div>

        <form method="POST" action="/chat/send" class="message-form" id="message-form">
            <?= csrf_field() ?>
            <input type="hidden" name="dialogue_id" value="<?= htmlspecialchars((string)($dialogue_id ?? 1), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <textarea name="content" rows="3" maxlength="4000" placeholder="Введите сообщение..." required></textarea>
            <button type="submit">Отправить</button>
        </form>
    </section>

    <script>
        document.getElementById('message-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);

            try {
                const response = await fetch('/chat/send', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    e.target.reset();
                    location.reload();
                } else {
                    alert(result.error || 'Ошибка при отправке');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка при отправке');
            }
        });
    </script>

<?php
$content = ob_get_clean();
$title = 'Чат';
$scripts = ['/js/chat.js'];
require __DIR__ . '/../layout.php';
?>

<?php $content = ob_get_clean(); $title = 'Чат'; require __DIR__ . '/../layout.php'; ?>