<?php ob_start(); ?>
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header__back">
                <a href="/" class="back-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </a>
            </div>
            <div class="chat-header__info">
                <div class="chat-avatar">
                    <?php if ($dialogue['type'] === 'group'): ?>
                        <div class="group-avatar">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="#667eea">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                    <?php else:
                        $otherUser = null;
                        if (!empty($dialogue['participants'])) {
                            foreach ($dialogue['participants'] as $p) {
                                if ($p['id'] != currentUserId()) {
                                    $otherUser = $p;
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="user-avatar">
                            <?php if (!empty($otherUser['avatar'])): ?>
                                <img src="<?= htmlspecialchars($otherUser['avatar']) ?>" alt="<?= htmlspecialchars($otherUser['name']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder" style="background: #667eea">
                                    <?= mb_substr($otherUser['name'] ?? '?', 0, 1) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="chat-header__details">
                    <h2><?= htmlspecialchars($dialogue['title'] ?? ($otherUser['name'] ?? 'Чат'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
                    <div class="chat-status" id="chat-status">
                        <?php if ($dialogue['type'] === 'group'): ?>
                            <span class="members-count">Участников: <?= $dialogue['members_count'] ?? 0 ?></span>
                        <?php else: ?>
                            <span class="user-status <?= ($otherUser['is_online'] ?? false) ? 'online' : 'offline' ?>" id="user-status">
                                <?= ($otherUser['is_online'] ?? false) ? 'Онлайн' : 'Был(а) недавно' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="chat-header__actions">
                <button class="icon-btn" id="search-messages-btn" title="Поиск">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                </button>
                <button class="icon-btn" id="chat-info-btn" title="Информация">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="messages-area" id="messages-container">
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
                        <div class="message <?= $message['user_id'] == currentUserId() ? 'message--out' : 'message--in' ?>"
                             data-message-id="<?= $message['id'] ?>">
                            <?php if ($message['user_id'] != currentUserId()): ?>
                                <div class="message-avatar">
                                    <?php if (!empty($message['avatar'])): ?>
                                        <img src="<?= htmlspecialchars($message['avatar']) ?>" alt="<?= htmlspecialchars($message['user_name']) ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder small"><?= mb_substr($message['user_name'], 0, 1) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?php if ($message['user_id'] != currentUserId()): ?>
                                    <div class="message-sender"><?= htmlspecialchars($message['user_name']) ?></div>
                                <?php endif; ?>
                                <div class="message-bubble">
                                    <div class="message-text"><?= nl2br(htmlspecialchars($message['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></div>
                                    <div class="message-meta">
                                        <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                                        <?php if ($message['user_id'] == currentUserId()): ?>
                                            <span class="message-status"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-messages">
                        <div class="empty-messages__icon"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                        <p>Нет сообщений</p>
                        <span>Напишите первое сообщение</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="input-attachments">
                <button class="attach-btn" id="attach-btn" title="Прикрепить файл">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                </button>
                <input type="file" id="file-input" multiple style="display: none">
            </div>
            <div class="input-wrapper">
                <textarea id="message-input" placeholder="Сообщение..." rows="1" maxlength="4000"></textarea>
                <button class="emoji-btn" id="emoji-btn" title="Эмодзи">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                        <line x1="9" y1="9" x2="9.01" y2="9"/>
                        <line x1="15" y1="9" x2="15.01" y2="9"/>
                    </svg>
                </button>
            </div>
            <button class="send-btn" id="send-btn" title="Отправить">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>

        <div class="typing-indicator" id="typing-indicator" style="display: none;">
            <span></span><span></span><span></span>
            <span class="typing-text">печатает...</span>
        </div>
    </div>

    <form method="POST" action="/chat/send" id="message-form" style="display: none;">
        <?= csrf_field() ?>
        <input type="hidden" name="dialogue_id" value="<?= htmlspecialchars((string)($dialogue_id ?? 1)) ?>">
        <textarea name="content"></textarea>
    </form>

    <style>
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 80px);
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        .chat-header__back { display: none; }
        .back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: #667eea;
            transition: background 0.2s;
        }
        .back-btn:hover { background: #f3f4f6; }
        .chat-header__info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        .user-avatar, .group-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }
        .avatar-placeholder.small {
            width: 32px;
            height: 32px;
            font-size: 14px;
            border-radius: 50%;
        }
        .chat-header__details h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: #1f2937;
        }
        .chat-status { font-size: 12px; color: #6b7280; }
        .chat-status .online { color: #10b981; }
        .chat-status .offline { color: #9ca3af; }
        .chat-header__actions { display: flex; gap: 8px; }
        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
        }
        .icon-btn:hover { background: #f3f4f6; color: #667eea; }
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .messages-list {
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
        }
        .date-divider {
            text-align: center;
            margin: 20px 0;
        }
        .date-divider span {
            font-size: 12px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .message {
            display: flex;
            margin-bottom: 16px;
            animation: fadeInUp 0.2s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message--out { justify-content: flex-end; }
        .message--in { justify-content: flex-start; }
        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 8px;
            flex-shrink: 0;
        }
        .message-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .message-content { max-width: 65%; }
        .message--out .message-content { max-width: 65%; }
        .message-sender {
            font-size: 12px;
            font-weight: 500;
            color: #667eea;
            margin-bottom: 4px;
            margin-left: 4px;
        }
        .message-bubble {
            background: white;
            border-radius: 18px;
            padding: 8px 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .message--out .message-bubble { background: #667eea; color: white; }
        .message-text { font-size: 14px; line-height: 1.4; word-wrap: break-word; }
        .message--out .message-text { color: white; }
        .message-meta {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            margin-top: 4px;
            font-size: 10px;
        }
        .message--out .message-meta { color: rgba(255,255,255,0.7); }
        .message--in .message-meta { color: #9ca3af; }
        .message-status svg { width: 12px; height: 12px; }
        .empty-messages {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-messages__icon { margin-bottom: 16px; }
        .empty-messages p {
            font-size: 18px;
            font-weight: 500;
            margin: 0 0 8px 0;
            color: #6b7280;
        }
        .chat-input-area {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            padding: 16px 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        .attach-btn, .emoji-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border: none;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .attach-btn:hover, .emoji-btn:hover { background: #e5e7eb; color: #667eea; }
        .input-wrapper {
            flex: 1;
            position: relative;
        }
        #message-input {
            width: 100%;
            min-height: 40px;
            max-height: 120px;
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            outline: none;
            transition: border-color 0.2s;
            background: #f9fafb;
        }
        #message-input:focus {
            border-color: #667eea;
            background: white;
        }
        .send-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #667eea;
            border: none;
            cursor: pointer;
            color: white;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .send-btn:hover { background: #5a67d8; transform: scale(1.02); }
        .send-btn:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; }
        .typing-indicator {
            position: fixed;
            bottom: 80px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 4px;
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 13px;
            color: #6b7280;
            z-index: 100;
        }
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 50%;
            display: inline-block;
            animation: typing 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }
        .typing-text { margin-left: 8px; }
        .messages-area::-webkit-scrollbar { width: 6px; }
        .messages-area::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        .messages-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .message-context-menu {
            position: fixed;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 8px 0;
            z-index: 1000;
            min-width: 180px;
        }
        .message-context-menu button {
            width: 100%;
            padding: 10px 16px;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #1f2937;
            transition: background 0.2s;
        }
        .message-context-menu button:hover { background: #f3f4f6; }
        .message-context-menu button.danger { color: #ef4444; }
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 60px);
                border-radius: 0;
                margin: -20px;
            }
            .chat-header__back { display: block; }
            .chat-header { padding: 10px 16px; }
            .messages-area { padding: 12px; }
            .message-content, .message--out .message-content { max-width: 85%; }
            .chat-input-area { padding: 12px 16px; gap: 8px; }
            .attach-btn, .emoji-btn, .send-btn { width: 36px; height: 36px; }
        }
    </style>

    <script src="/js/chat.js"></script>
<?php
$content = ob_get_clean();
$title = 'Чат';
require __DIR__ . '/../layout.php';
?>