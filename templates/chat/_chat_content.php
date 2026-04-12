<?php
// templates/chat/_chat_content.php
?>
<input type="hidden" name="dialogue_id" value="<?= $dialogue_id ?>">

<!-- Шапка чата -->
<div class="chat-header">
    <div class="chat-header-info" onclick="goToProfile(<?= $otherUser['id'] ?? 0 ?>)">
        <div class="chat-header-avatar">
            <?php if ($dialogue['type'] === 'group'): ?>
                <div class="avatar-placeholder group-avatar" style="background: #667eea; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            <?php else: ?>
                <?php if (!empty($otherUser['avatar'])): ?>
                    <img src="<?= htmlspecialchars($otherUser['avatar']) ?>" alt="<?= htmlspecialchars($otherUser['name']) ?>">
                <?php else: ?>
                    <div class="avatar-placeholder" style="background: #667eea">
                        <?= mb_substr($otherUser['name'] ?? '?', 0, 1) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="chat-header-details">
            <h3><?= htmlspecialchars($dialogue['title'] ?? ($otherUser['name'] ?? 'Чат')) ?></h3>
            <p class="<?= ($otherUser['is_online'] ?? false) ? 'online' : '' ?>">
                <?php if ($dialogue['type'] === 'group'): ?>
                    Участников: <?= $dialogue['members_count'] ?? 0 ?>
                <?php else: ?>
                    <?= ($otherUser['is_online'] ?? false) ? 'Онлайн' : 'Был(а) ' . date('d.m.Y H:i', strtotime($otherUser['last_seen'] ?? 'now')) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="chat-header-actions">
        <div class="menu-dots" onclick="toggleDropdown()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="19" cy="12" r="1"/>
                <circle cx="5" cy="12" r="1"/>
            </svg>
        </div>
        <div class="dropdown-menu" id="chat-dropdown">
            <div class="dropdown-item" onclick="goToProfile(<?= $otherUser['id'] ?? 0 ?>)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Профиль</span>
            </div>
            <div class="dropdown-divider"></div>
            <div class="dropdown-item" onclick="clearChat()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 6h18M8 6V4h8v2"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                </svg>
                <span>Очистить чат</span>
            </div>
            <div class="dropdown-item danger" onclick="blockUser()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="18" y1="6" x2="6" y2="18"/>
                </svg>
                <span>Заблокировать</span>
            </div>
            <div class="dropdown-item danger" onclick="blockAndClear()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <path d="M3 6h18M8 6V4h8v2"/>
                </svg>
                <span>Заблокировать и очистить</span>
            </div>
        </div>
    </div>
</div>

<!-- Сообщения -->
<div class="messages-container" id="messages-container">
    <div class="messages-list" id="messages-list">
        <?php if (!empty($messages)): ?>
            <?php
            $lastDate = '';
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
                <div class="date-divider"><span><?= $displayDate ?></span></div>
            <?php endif; ?>
                <div class="message <?= $message['user_id'] == currentUserId() ? 'message--out' : 'message--in' ?>">
                    <?php if ($message['user_id'] != currentUserId()): ?>
                        <div class="message-avatar" onclick="goToProfile(<?= $message['user_id'] ?>)">
                            <?php if (!empty($message['avatar'])): ?>
                                <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="<?= htmlspecialchars($message['user_name']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder small" style="background: #667eea; font-size: 14px;">
                                    <?= mb_substr($message['user_name'], 0, 1) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="message-content">
                        <?php if ($message['user_id'] != currentUserId()): ?>
                            <div class="message-sender"><?= htmlspecialchars($message['user_name']) ?></div>
                        <?php endif; ?>
                        <div class="message-bubble">
                            <div class="message-text"><?= nl2br(htmlspecialchars($message['content'])) ?></div>
                            <div class="message-meta">
                                <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                                <?php if ($message['user_id'] == currentUserId()): ?>
                                    <span class="message-status">✓✓</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #65676b;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p style="margin-top: 16px;">Нет сообщений</p>
                <span>Напишите первое сообщение</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ввод сообщения -->
<div class="chat-input-area">
    <button class="attach-btn" id="attach-btn" title="Прикрепить">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        </svg>
    </button>
    <div class="input-wrapper">
        <textarea id="message-input" placeholder="Сообщение..." rows="1"></textarea>
    </div>
    <button class="send-btn" id="send-btn" title="Отправить">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
    </button>
</div>

<style>
    .small {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
</style>