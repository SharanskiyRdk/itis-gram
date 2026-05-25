<?php
// Usage: php bin/create_group_chats.php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$opts = getopt('', ['dry-run']);
$dryRun = isset($opts['dry-run']);

$groups = [];
// 11-400 .. 11-412, 11-413a
for ($i = 400; $i <= 412; $i++) {
    $groups[] = '11-' . $i;
}
$groups[] = '11-413a';

$db = Database::getInstance();

// Try to find a creator (admin) to set as created_by
$admin = $db->fetchOne("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$createdBy = $admin['id'] ?? null;

foreach ($groups as $group) {
    // Check if dialogue exists
    $exists = $db->fetchOne('SELECT id FROM dialogues WHERE type = :type AND title = :title AND is_deleted = FALSE', ['type' => 'group', 'title' => $group]);
    if ($exists) {
        echo "[skip] Group '{$group}' already exists (id={$exists['id']}).\n";
        continue;
    }

    echo ($dryRun ? '[dry-run] ' : '') . "Creating group '{$group}'...\n";

    if ($dryRun) {
        // show how many users would be added
        $users = $db->fetchAll('SELECT id FROM users WHERE student_group = :group AND is_deleted = FALSE', ['group' => $group]);
        echo "  participants found: " . count($users) . "\n";
        continue;
    }

    // Create dialogue
    $db->beginTransaction();
    try {
        $params = ['title' => $group];
        if ($createdBy) {
            $sql = "INSERT INTO dialogues (type, title, created_by, created_at, updated_at) VALUES ('group', :title, :created_by, NOW(), NOW())";
            $params['created_by'] = $createdBy;
            $db->execute($sql, $params);
        } else {
            $db->execute("INSERT INTO dialogues (type, title, created_at, updated_at) VALUES ('group', :title, NOW(), NOW())", $params);
        }

        $dialogueId = $db->lastInsertId();

        // Add all users from that student_group
        $users = $db->fetchAll('SELECT id FROM users WHERE student_group = :group AND is_deleted = FALSE', ['group' => $group]);
        foreach ($users as $u) {
            $db->execute('INSERT INTO dialogue_users (dialogue_id, user_id, joined_at) VALUES (:did, :uid, NOW())', ['did' => $dialogueId, 'uid' => $u['id']]);
        }

        // optional system message
        $db->execute('INSERT INTO messages (dialogue_id, user_id, content, created_at) VALUES (:did, NULL, :content, NOW())', ['did' => $dialogueId, 'content' => 'Group created for ' . $group]);

        $db->commit();
        echo "[ok] Created group '{$group}' id={$dialogueId}, participants=" . count($users) . "\n";
    } catch (\Exception $e) {
        $db->rollBack();
        echo "[error] Failed to create group '{$group}': " . $e->getMessage() . "\n";
    }
}
