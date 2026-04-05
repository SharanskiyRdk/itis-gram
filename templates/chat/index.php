<?php ob_start(); ?>
    <section class="chat-list">
        <h1>Диалоги</h1>

        <div class="chat-list__actions">
            <form method="POST" action="/chat/create" class="form form--inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <input type="text" name="title" placeholder="Название группового чата" maxlength="255">
                <button type="submit">Создать групповой чат</button>
            </form>
        </div>

        <div class="chat-list__items">
            <?php if (empty($dialogues)): ?>
                <p>У вас пока нет диалогов. Начните общение с другим пользователем!</p>
            <?php else: ?>
                <?php foreach ($dialogues as $dialogue): ?>
                    <article class="chat-item">
                        <a href="/chat?id=<?= $dialogue['id'] ?>">
                            <?= htmlspecialchars($dialogue['title'] ?? 'Личный чат', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </a>
                        <?php if (!empty($dialogue['last_message'])): ?>
                            <p class="chat-item__last-message">
                                <?= htmlspecialchars(mb_substr($dialogue['last_message'], 0, 50), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
<?php $content = ob_get_clean(); $title = 'Чаты'; require __DIR__ . '/../layout.php'; ?>