<?php
// Временный фикс
if (!isset($dialogue) && isset($dialogue_id)) {
    $dialogueObj = \App\Models\Dialogue::find($dialogue_id);
    if ($dialogueObj) {
        $dialogue = [
            'type' => $dialogueObj->getType(),
            'title' => $dialogueObj->getTitle(),
            'group_code' => $dialogueObj->getGroupCode(),
            'avatar' => $dialogueObj->getAvatar(),
        ];
    }
}

$isGroup = ($dialogue['type'] ?? '') === 'group';
$membersCount = $membersCount ?? 0;
$canManageGroup = $canManageGroup ?? false;
$participants = $participants ?? [];
$groupTitleLocked = $groupTitleLocked ?? false;
$isBlocked = $isBlocked ?? false;
$blockState = $blockState ?? ['blocked_by_me' => false, 'blocked_by_other' => false, 'partner_id' => null];
?>
<input type="hidden" name="dialogue_id" value="<?= htmlspecialchars($dialogue_id) ?>">

<!-- Шапка чата -->
<div class="chat-header">
    <div class="chat-header-info<?= $isGroup ? ' chat-header-info--group' : '' ?>"<?= $isGroup ? ' onclick="openGroupInfo()"' : ' onclick="goToProfile(' . (int)($otherUser['id'] ?? 0) . ')"' ?>>
        <div class="chat-header-avatar">
            <?php if ($isGroup): ?>
                <?php if (!empty($dialogue['avatar'])): ?>
                    <img src="<?= htmlspecialchars($dialogue['avatar']) ?>" alt="<?= htmlspecialchars($dialogue['title'] ?? 'Группа') ?>">
                <?php else: ?>
                    <div class="avatar-placeholder group-avatar" style="background: #667eea; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                <?php endif; ?>
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
                <?php if ($isGroup): ?>
                    <?= !empty($dialogue['group_code']) ? 'Группа: ' . htmlspecialchars($dialogue['group_code']) . ' · ' : '' ?>Участников: <?= (int)$membersCount ?>
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
            <?php if (!$isGroup): ?>
                <div class="dropdown-item" onclick="goToProfile(<?= $otherUser['id'] ?? 0 ?>)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span>Профиль</span>
                </div>
                <div class="dropdown-divider"></div>
            <?php else: ?>
                <div class="dropdown-divider"></div>
            <?php endif; ?>
            <?php if (!$isGroup): ?>
                <div class="dropdown-item" onclick="clearChat('me')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 6h18M8 6V4h8v2"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    </svg>
                    <span>Очистить чат у меня</span>
                </div>
                <div class="dropdown-item" onclick="clearChat('all')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M3 6h18M8 6V4h8v2"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                    </svg>
                    <span>Очистить чат у всех</span>
                </div>
                <?php if (!empty($blockState['blocked_by_me'])): ?>
                    <div class="dropdown-item" onclick="unblockUser()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M8 12h8"/>
                        </svg>
                        <span>Разблокировать</span>
                    </div>
                <?php else: ?>
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
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isGroup): ?>
    <div id="group-info-modal" class="modal">
        <div class="modal-content modal-content--members modal-content--group-info">
            <div class="modal-header">
                <h3>Группа</h3>
                <div class="modal-close">&times;</div>
            </div>
            <div class="modal-body group-info-layout">
                <div class="group-info-header">
                    <div class="group-info-header__avatar">
                        <?php if (!empty($dialogue['avatar'])): ?>
                            <img src="<?= htmlspecialchars($dialogue['avatar']) ?>" alt="<?= htmlspecialchars($dialogue['title'] ?? 'Группа') ?>">
                        <?php else: ?>
                            <div class="group-avatar-preview__placeholder"><?= mb_substr($dialogue['title'] ?? 'G', 0, 1) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="group-info-header__meta">
                        <div class="group-info-header__title"><?= htmlspecialchars($dialogue['title'] ?? 'Группа') ?></div>
                        <div class="group-info-header__subtitle">
                            <?= !empty($dialogue['group_code']) ? 'Группа: ' . htmlspecialchars($dialogue['group_code']) : 'Академическая группа' ?>
                        </div>
                        <div class="group-info-header__subtitle">Участников: <?= (int)$membersCount ?></div>
                    </div>
                </div>

                <div class="group-info-sections">
                    <?php if ($canManageGroup): ?>
                        <section class="group-info-section">
                            <div class="group-info-section__title">Настройки группы</div>
                            <form id="group-settings-form" class="group-settings-form">
                                <input type="hidden" name="dialogue_id" value="<?= htmlspecialchars($dialogue_id) ?>">
                                <div class="form-group">
                                    <label>Название группы</label>
                                    <?php if ($groupTitleLocked): ?>
                                        <input type="text" value="<?= htmlspecialchars($dialogue['title'] ?? '') ?>" readonly>
                                        <div class="form-hint">Название академической группы нельзя изменять.</div>
                                    <?php else: ?>
                                        <input type="text" name="title" value="<?= htmlspecialchars($dialogue['title'] ?? '') ?>" placeholder="Название группы">
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Фото группы</label>
                                    <div class="group-avatar-preview">
                                        <?php if (!empty($dialogue['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($dialogue['avatar']) ?>" alt="Group avatar">
                                        <?php else: ?>
                                            <div class="group-avatar-preview__placeholder"><?= mb_substr($dialogue['title'] ?? 'G', 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                                </div>
                                <button type="submit" class="btn" style="width: 100%;">Сохранить</button>
                            </form>
                        </section>
                    <?php endif; ?>

                    <section class="group-info-section">
                        <div class="group-info-section__title">Участники</div>
                        <div class="group-members-list">
                            <?php foreach ($participants as $participant): ?>
                                <div class="group-member-card">
                                    <div class="group-member-card__avatar" onclick="openUserCard(<?= (int)$participant['id'] ?>)">
                                        <?php if (!empty($participant['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($participant['avatar']) ?>" alt="<?= htmlspecialchars($participant['name']) ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder small" style="background: #667eea; font-size: 14px;">
                                                <?= mb_substr($participant['name'] ?? '?', 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="group-member-card__info">
                                        <div class="group-member-card__name"><?= htmlspecialchars($participant['name']) ?></div>
                                        <div class="group-member-card__meta">
                                            <?= !empty($participant['student_group']) ? htmlspecialchars($participant['student_group']) : 'Участник группы' ?>
                                        </div>
                                    </div>
                                    <?php if ((int)$participant['id'] !== currentUserId()): ?>
                                        <button type="button" class="group-member-card__action" onclick="startPrivateChat(<?= (int)$participant['id'] ?>)">Написать</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Сообщения -->
<div class="messages-container" id="messages-container">
    <?php require __DIR__ . '/_messages.php'; ?>
</div>

<?php if (!$isGroup && $isBlocked): ?>
    <div class="chat-blocked-notice">
        <?= !empty($blockState['blocked_by_me']) ? 'Чат заблокирован. Сначала разблокируйте пользователя.' : 'Этот пользователь заблокировал вас. Отправка сообщений недоступна.' ?>
    </div>
<?php endif; ?>

<!-- Ввод сообщения -->
<div class="chat-input-area<?= (!$isGroup && $isBlocked) ? ' chat-input-area--blocked' : '' ?>" data-blocked="<?= (!$isGroup && $isBlocked) ? '1' : '0' ?>">
    <button type="button" class="attach-btn" id="attach-btn" title="Прикрепить"<?= (!$isGroup && $isBlocked) ? ' disabled' : '' ?>>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        </svg>
    </button>
    <div class="input-wrapper">
        <textarea id="message-input" placeholder="<?= (!$isGroup && $isBlocked) ? 'Чат заблокирован' : 'Сообщение...' ?>" rows="1"<?= (!$isGroup && $isBlocked) ? ' disabled' : '' ?>></textarea>
        <input type="file" id="message-attach-input" class="message-attach-input" accept="image/*,video/mp4,video/webm,video/quicktime,audio/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">

    </div>
    <button type="button" class="send-btn" id="send-btn" title="Отправить" onclick="sendChatMessage()"<?= (!$isGroup && $isBlocked) ? ' disabled' : '' ?>>
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