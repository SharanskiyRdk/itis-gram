<?php
/** @var array $stats */
?>
<?php ob_start(); ?>
    <section class="admin-hero">
        <div>
            <p class="admin-kicker">Control center</p>
            <h1>Dashboard</h1>
        </div>
        <a class="admin-link" href="/admin/tickets">Open tickets</a>
    </section>

    <div class="admin-stats">
        <article class="admin-card stat-card">
            <span class="stat-label">Users</span>
            <strong><?= htmlspecialchars($stats['users'] ?? 0) ?></strong>
        </article>
        <article class="admin-card stat-card">
            <span class="stat-label">Open tickets</span>
            <strong><?= htmlspecialchars($stats['tickets_open'] ?? 0) ?></strong>
        </article>
    </div>
<?php
$content = ob_get_clean();
$title = 'Admin Dashboard';
require __DIR__ . '/layout.php';
