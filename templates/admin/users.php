<?php
/** @var array $users */
/** @var int $page */
/** @var int $perPage */
/** @var int $total */
/** @var string $q */
?>
<?php ob_start(); ?>
    <section class="admin-hero">
        <div>
            <p class="admin-kicker">User management</p>
            <h1>Users</h1>
        </div>
    </section>

    <form method="get" action="/admin/users" class="admin-toolbar">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name or email" class="admin-search">
        <button type="submit" class="admin-button">Search</button>
    </form>

    <div class="admin-card admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Group</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['id']) ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['student_group'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['role'] ?? 'user') ?></td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <form method="post" action="/admin/users/role" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken()) ?>">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                        <select name="role" class="admin-select">
                            <option value="user">user</option>
                            <option value="admin">admin</option>
                        </select>
                        <button type="submit" class="admin-button admin-button--soft">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php
    $pages = (int)ceil($total / $perPage);
    if ($pages > 1):
    ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="/admin/users?page=<?= $p ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" <?= $p === $page ? 'style="font-weight:bold"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Users';
require __DIR__ . '/layout.php';
