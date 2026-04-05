<?php ob_start(); ?>
    <section class="search">
        <h1>Поиск пользователей</h1>

        <form method="GET" action="/search/users" class="form">
            <input type="text" name="q" placeholder="Имя или email" maxlength="100" value="<?= htmlspecialchars($q ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <button type="submit">Найти</button>
        </form>

        <div class="search-results">
            <article class="user-card">
                <strong>Алексей</strong>
                <p>alex@example.com</p>
            </article>
        </div>
    </section>
<?php $content = ob_get_clean(); $title = 'Поиск'; require __DIR__ . '/../layout.php'; ?>