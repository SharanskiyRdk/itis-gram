<?php
/** @var array $tickets */
/** @var int $page */
?>
<?php ob_start(); ?>
    <section class="admin-hero">
        <div>
            <p class="admin-kicker">Support</p>
            <h1>Tickets</h1>
        </div>
    </section>

    <?php if (empty($tickets)): ?>
        <div class="admin-card admin-empty">No tickets found.</div>
    <?php else: ?>
        <div class="admin-card admin-table-wrap">
        <table class="admin-table admin-table--tickets">
            <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Subject</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['id']) ?></td>
                    <td>
                        <div class="user-cell">
                            <strong><?= htmlspecialchars($t['user_nickname'] ?? 'Unknown') ?></strong>
                            <span>#<?= htmlspecialchars($t['user_id']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($t['user_email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($t['subject']) ?></td>
                    <td><span class="ticket-pill ticket-pill--<?= htmlspecialchars($t['status']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                    <td><?= htmlspecialchars($t['created_at']) ?></td>
                    <td>
                        <form method="post" action="/admin/tickets/resolve" class="inline-form inline-form--stacked">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($this->csrfToken()) ?>">
                            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($t['id']) ?>">
                            <input type="text" name="response" placeholder="Response" class="admin-input">
                            <div style="display:flex;gap:8px">
                                <button type="submit" name="action" value="resolve" class="admin-button">Resolve</button>
                                <button type="submit" name="action" value="reject" class="admin-button admin-button--soft">Reject</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Tickets';
require __DIR__ . '/layout.php';
