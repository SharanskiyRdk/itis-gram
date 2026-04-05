-- Создание базы данных
CREATE DATABASE itisgram;

-- Таблица users
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

-- Таблица dialogues
CREATE TABLE dialogues (
                           id SERIAL PRIMARY KEY,
                           type VARCHAR(20) NOT NULL CHECK (type IN ('private', 'group')),
                           title VARCHAR(255),
                           created_by INT REFERENCES users(id),
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица dialogue_users
CREATE TABLE dialogue_users (
                                id SERIAL PRIMARY KEY,
                                dialogue_id INT NOT NULL REFERENCES dialogues(id) ON DELETE CASCADE,
                                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                last_read_at TIMESTAMP,
                                UNIQUE(dialogue_id, user_id)
);

-- Таблица messages
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

-- Таблица message_reactions
CREATE TABLE message_reactions (
                                   id SERIAL PRIMARY KEY,
                                   message_id INT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
                                   user_id INT NOT NULL REFERENCES users(id),
                                   reaction VARCHAR(10) NOT NULL,
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   UNIQUE(message_id, user_id)
);

-- Создание индексов
CREATE INDEX idx_messages_dialogue_id ON messages(dialogue_id);
CREATE INDEX idx_messages_user_id ON messages(user_id);
CREATE INDEX idx_dialogue_users_user_id ON dialogue_users(user_id);
CREATE INDEX idx_dialogue_users_dialogue_id ON dialogue_users(dialogue_id);
CREATE INDEX idx_users_email ON users(email);

-- Создание тестового пользователя (пароль: password123)
INSERT INTO users (name, email, password, created_at)
VALUES ('Admin', 'admin@itisgram.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());