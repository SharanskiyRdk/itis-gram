<?php
// Partial: message list for AJAX refresh
// $wrap - оборачивать ли в <div class="messages-list">
// $previousDate - для группировки дат
$wrap = $wrap ?? true;
$previousDate = $previousDate ?? '';
$lastMessage = !empty($messages) ? end($messages) : null;
$lastMessageId = $lastMessage['id'] ?? '';
$lastMessageDate = $lastMessage ? date('Y-m-d', strtotime($lastMessage['created_at'])) : '';
?>
<?php if ($wrap): ?>
<div class="messages-list" id="messages-list" data-last-message-id="<?= htmlspecialchars((string)$lastMessageId) ?>" data-last-message-date="<?= htmlspecialchars($lastMessageDate) ?>">
<?php endif; ?>
    
    <?php if (!empty($messages)): ?>
        <?php
        $lastDate = $previousDate;
        foreach ($messages as $message):
            $messageDate = date('Y-m-d', strtotime($message['created_at']));
            $displayDate = '';
            if ($lastDate !== $messageDate) {
                $lastDate = $messageDate;
                $timestamp = strtotime($message['created_at']);
                if (date('Y-m-d') === $messageDate) {
                    $displayDate = 'Сегодня';
                } elseif (date('Y-m-d', strtotime('-1 day')) === $messageDate) {
                    $displayDate = 'Вчера';
                } else {
                    $displayDate = date('d.m.Y', $timestamp);
                }
            }
            ?>
            <?php if ($displayDate): ?>
                <div class="date-divider"><span><?= htmlspecialchars($displayDate) ?></span></div>
            <?php endif; ?>
            
            <?php require __DIR__ . '/_message.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <?php if ($wrap): ?>
            <div style="text-align: center; padding: 40px; color: #65676b;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p style="margin-top: 16px;">Нет сообщений</p>
                <span>Напишите первое сообщение</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
<?php if ($wrap): ?>
</div>
<?php endif; ?>