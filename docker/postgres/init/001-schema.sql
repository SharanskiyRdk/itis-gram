CREATE TABLE IF NOT EXISTS users (
                       id SERIAL PRIMARY KEY,
                       name VARCHAR(100) NOT NULL,
                       email VARCHAR(255) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL,
                       avatar VARCHAR(255),
                       bio TEXT,
                       is_online BOOLEAN DEFAULT FALSE,
                       last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       is_deleted BOOLEAN DEFAULT FALSE,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dialogues (
                           id SERIAL PRIMARY KEY,
                           type VARCHAR(20) NOT NULL CHECK (type IN ('private', 'group')),
                           title VARCHAR(255),
                           created_by INT REFERENCES users(id),
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS dialogue_users (
                                id SERIAL PRIMARY KEY,
                                dialogue_id INT NOT NULL REFERENCES dialogues(id) ON DELETE CASCADE,
                                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                last_read_at TIMESTAMP,
                                last_cleared_at TIMESTAMP,
                                UNIQUE(dialogue_id, user_id)
);
CREATE TABLE IF NOT EXISTS friendships (
                          id SERIAL PRIMARY KEY,
                          user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                          friend_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          UNIQUE(user_id, friend_id)
);
CREATE TABLE IF NOT EXISTS user_blocks (
                          id SERIAL PRIMARY KEY,
                          blocker_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                          blocked_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          UNIQUE(blocker_id, blocked_id)
);
CREATE TABLE IF NOT EXISTS messages (
                          id SERIAL PRIMARY KEY,
                          dialogue_id INT NOT NULL REFERENCES dialogues(id) ON DELETE CASCADE,
                          user_id INT NOT NULL REFERENCES users(id),
                          content TEXT,
                          file_path VARCHAR(255),
                          file_type VARCHAR(50),
                          file_size INT,
                          is_read BOOLEAN DEFAULT FALSE,
                          is_deleted BOOLEAN DEFAULT FALSE,
                          reply_to INT REFERENCES messages(id),
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS message_reactions (
                                   id SERIAL PRIMARY KEY,
                                   message_id INT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
                                   user_id INT NOT NULL REFERENCES users(id),
                                   reaction VARCHAR(10) NOT NULL,
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   UNIQUE(message_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_messages_dialogue_id ON messages(dialogue_id);
CREATE INDEX IF NOT EXISTS idx_messages_user_id ON messages(user_id);
CREATE INDEX IF NOT EXISTS idx_dialogue_users_user_id ON dialogue_users(user_id);
CREATE INDEX IF NOT EXISTS idx_dialogue_users_dialogue_id ON dialogue_users(dialogue_id);
CREATE INDEX IF NOT EXISTS idx_dialogue_users_last_cleared_at ON dialogue_users(last_cleared_at);
CREATE INDEX IF NOT EXISTS idx_friendships_user_id ON friendships(user_id);
CREATE INDEX IF NOT EXISTS idx_friendships_friend_id ON friendships(friend_id);
CREATE INDEX IF NOT EXISTS idx_user_blocks_blocker_id ON user_blocks(blocker_id);
CREATE INDEX IF NOT EXISTS idx_user_blocks_blocked_id ON user_blocks(blocked_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

ALTER TABLE users ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS session_created_at TIMESTAMP NULL;

CREATE INDEX IF NOT EXISTS idx_users_session_id ON users(session_id);
 
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified_student BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_group VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS ban_reason TEXT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_at TIMESTAMP NULL;

-- Таблица для обращений в поддержку
CREATE TABLE IF NOT EXISTS support_tickets (
                                 id SERIAL PRIMARY KEY,
                                 user_id INT NOT NULL REFERENCES users(id),
                                 subject VARCHAR(255) NOT NULL,
                                 message TEXT NOT NULL,
                                 status VARCHAR(20) DEFAULT 'open', -- open, in_progress, resolved, closed
                                 admin_response TEXT,
                                 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица для администраторов
CREATE TABLE IF NOT EXISTS admins (
                        id SERIAL PRIMARY KEY,
                        user_id INT NOT NULL REFERENCES users(id),
                        role VARCHAR(50) DEFAULT 'admin', -- admin, support
                        permissions JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Индексы
CREATE INDEX IF NOT EXISTS idx_users_verified ON users(is_verified_student);
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_support_tickets_user_id ON support_tickets(user_id);

ALTER TABLE dialogues ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE;

-- Add role to users (default 'user')
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'user';
INSERT INTO users (name, email, password, role, created_at) VALUES ('Admin', 'admin@itisgram.ru', '$2y$12$DDsTJu0MlvW6nkQCw6otC.BGw9xpQRKvf9FMWfaEDG9TVT3A0qK2G', 'admin', NOW())
ON CONFLICT (email) DO NOTHING;

-- Create default group dialogues and add users by their student_group
DO $$
DECLARE
    grp TEXT;
    grp_list TEXT[] := ARRAY[
        '11-400','11-401','11-402','11-403','11-404','11-405','11-406','11-407','11-408','11-409','11-410','11-411','11-412','11-413a'
    ];
    d_id INT;
BEGIN
    FOREACH grp IN ARRAY grp_list LOOP
        SELECT id INTO d_id FROM dialogues WHERE type = 'group' AND title = grp LIMIT 1;
        IF d_id IS NULL THEN
            INSERT INTO dialogues (type, title, created_by, created_at, updated_at) 
            VALUES ('group', grp, (SELECT id FROM users WHERE role = 'admin' LIMIT 1), NOW(), NOW())
            RETURNING id INTO d_id;
        END IF;

        -- Add users from that student_group to dialogue_users (idempotent)
        INSERT INTO dialogue_users (dialogue_id, user_id, joined_at)
        SELECT d_id, u.id, NOW()
        FROM users u
        WHERE u.student_group = grp
        ON CONFLICT (dialogue_id, user_id) DO NOTHING;
    END LOOP;
END$$;