CREATE DATABASE itisgram;

CREATE TABLE users (
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

CREATE TABLE dialogues (
                           id SERIAL PRIMARY KEY,
                           type VARCHAR(20) NOT NULL CHECK (type IN ('private', 'group')),
                           title VARCHAR(255),
                           created_by INT REFERENCES users(id),
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dialogue_users (
                                id SERIAL PRIMARY KEY,
                                dialogue_id INT NOT NULL REFERENCES dialogues(id) ON DELETE CASCADE,
                                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                last_read_at TIMESTAMP,
                                UNIQUE(dialogue_id, user_id)
);

CREATE TABLE messages (
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

CREATE TABLE message_reactions (
                                   id SERIAL PRIMARY KEY,
                                   message_id INT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
                                   user_id INT NOT NULL REFERENCES users(id),
                                   reaction VARCHAR(10) NOT NULL,
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   UNIQUE(message_id, user_id)
);

CREATE INDEX idx_messages_dialogue_id ON messages(dialogue_id);
CREATE INDEX idx_messages_user_id ON messages(user_id);
CREATE INDEX idx_dialogue_users_user_id ON dialogue_users(user_id);
CREATE INDEX idx_dialogue_users_dialogue_id ON dialogue_users(dialogue_id);
CREATE INDEX idx_users_email ON users(email);

INSERT INTO users (name, email, password, created_at)
VALUES ('Admin', 'admin@itisgram.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

ALTER TABLE users ADD COLUMN session_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN session_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN session_created_at TIMESTAMP NULL;

CREATE INDEX idx_users_session_id ON users(session_id);\

ALTER TABLE users ADD COLUMN is_verified_student BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN student_group VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN ban_reason TEXT NULL;
ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL;

-- Таблица для обращений в поддержку
CREATE TABLE support_tickets (
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
CREATE TABLE admins (
                        id SERIAL PRIMARY KEY,
                        user_id INT NOT NULL REFERENCES users(id),
                        role VARCHAR(50) DEFAULT 'moderator', -- admin, moderator, support
                        permissions JSON,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Индексы
CREATE INDEX idx_users_verified ON users(is_verified_student);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_support_tickets_user_id ON support_tickets(user_id);