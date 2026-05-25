<?php
// Partial: single message. Expects $message array with keys: user_id, user_name, avatar, content, created_at
$isOwn = (int)($message['user_id'] ?? 0) === currentUserId();
$content = trim((string)($message['content'] ?? ''));
$filePath = (string)($message['file_path'] ?? '');
$fileType = (string)($message['file_type'] ?? 'file');
$fileName = $filePath !== '' ? basename($filePath) : '';
$fileExt = $fileName !== '' ? strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) : '';
?>
<div class="message <?= $isOwn ? 'message--out' : 'message--in' ?>" data-message-id="<?= (int)$message['id'] ?>" data-message-date="<?= htmlspecialchars(date('Y-m-d', strtotime($message['created_at']))) ?>">
    <?php if (!$isOwn): ?>
        <div class="message-avatar" onclick="openUserCard(<?= (int)$message['user_id'] ?>)">
            <?php if (!empty($message['avatar'])): ?>
                <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="<?= htmlspecialchars($message['user_name']) ?>">
            <?php else: ?>
                <div class="avatar-placeholder small" style="background: #667eea; font-size: 14px;">
                    <?= mb_substr((string)($message['user_name'] ?? '?'), 0, 1) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="message-content">
        <?php if (!$isOwn): ?>
            <div class="message-sender"><?= htmlspecialchars((string)($message['user_name'] ?? '')) ?></div>
        <?php endif; ?>
        <div class="message-bubble">
            <?php if ($content !== ''): ?>
                <div class="message-text"><?= nl2br(htmlspecialchars($content)) ?></div>
            <?php endif; ?>

            <?php if ($filePath !== ''): ?>
                <div class="message-attachment message-attachment--<?= htmlspecialchars($fileType) ?>">
                    <?php if ($fileType === 'image'): ?>
                        <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" rel="noopener noreferrer" class="message-attachment__media">
                            <img src="<?= htmlspecialchars($filePath) ?>" alt="<?= htmlspecialchars($fileName) ?>">
                        </a>
                    <?php elseif ($fileType === 'video'): ?>
                        <video controls playsinline preload="metadata" class="message-attachment__media">
                            <source src="<?= htmlspecialchars($filePath) ?>">
                        </video>
                    <?php elseif ($fileType === 'audio'): ?>
                        <audio controls preload="metadata" class="message-attachment__audio">
                            <source src="<?= htmlspecialchars($filePath) ?>">
                        </audio>
                    <?php else: ?>
                        <a class="message-file" href="<?= htmlspecialchars($filePath) ?>" target="_blank" rel="noopener noreferrer" download>
                            <span class="message-file__icon">📎</span>
                            <span class="message-file__info">
                                <span class="message-file__name"><?= htmlspecialchars($fileName !== '' ? $fileName : 'Файл') ?></span>
                                <span class="message-file__ext"><?= htmlspecialchars($fileExt !== '' ? strtoupper($fileExt) : 'FILE') ?></span>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="message-meta">
                <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                <?php if ($isOwn): ?>
                    <span class="message-status"><?= !empty($message['is_read']) ? '✓✓' : '✓' ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
