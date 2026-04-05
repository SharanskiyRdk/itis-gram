ItisGram — это учебный мессенджер с элементами социальной сети, где основной фокус на обмен сообщениями в реальном времени и обмен мультимедиа. Название сохраняет отсылку к ИТИС и Instagram, но функционально это гибрид мессенджера и мини-соцсети.

Полное описание проекта ItisGram (Мессенджер)
1. Концепция проекта
ItisGram — учебный мессенджер с поддержкой обмена сообщениями в реальном времени и мультимедиа (изображения, видео, файлы). Проект разрабатывается для изучения fullstack-разработки на PHP с фокусом на WebSocket технологии.

Ключевая особенность: реальное время, обмен файлами, групповые чаты.

Тип проекта: Монолитное веб-приложение с WebSocket сервером для реального времени.

2. Цели и задачи
Учебные цели:

Освоить WebSocket (Ratchet/Workerman) для real-time приложений

Научиться работать с файлами (загрузка, валидация, хранение)

Реализовать backend-логику на PHP (ООП, MVC)

Создать динамический frontend с WebSocket и AJAX

Обеспечить безопасность (XSS, SQL-инъекции, CSRF, шифрование)

Функциональные задачи:

Регистрация и авторизация

Личные и групповые чаты

Обмен сообщениями в реальном времени

Отправка изображений, видео и файлов (с ограничениями)

Онлайн/офлайн статусы

Индикатор набора текста

Аватар пользователя

Поиск пользователей

История сообщений

Комната (звонок групповой)

3. Архитектура проекта
text
itisgram/
├── public/              # Корневая папка веб-сервера
│   ├── index.php        # Точка входа
│   ├── css/             # Стили
│   ├── js/              # JavaScript (клиентский)
│   ├── uploads/         # Загруженные файлы
│   │   ├── avatars/     # Аватары пользователей
│   │   ├── images/      # Изображения в чатах
│   │   ├── videos/      # Видео в чатах
│   │   └── files/       # Прочие файлы
│   └── .htaccess
├── src/                 # Основной код
│   ├── Controller/      # Контроллеры
│   ├── Entity/          # Сущности Doctrine
│   ├── Repository/      # Репозитории
│   ├── Service/         # Сервисы (загрузка файлов)
│   ├── WebSocket/       # WebSocket сервер
│   └── Security/        # Безопасность
├── templates/           # Twig шаблоны
├── config/              # Конфигурация
├── docker/              # Docker настройки
├── .env                 # Переменные окружения
└── docker-compose.yml
4. Структура базы данных
Таблица users
Поле	Тип	Описание
id	INT	Первичный ключ
name	VARCHAR(100)	Имя пользователя
email	VARCHAR(255)	Email (уникальный)
password	VARCHAR(255)	Хеш пароля
avatar	VARCHAR(255)	Путь к аватару
bio	TEXT	О себе
is_online	BOOLEAN	Онлайн статус
last_seen	DATETIME	Последняя активность
created_at	DATETIME	Дата регистрации
Таблица dialogues
Поле	Тип	Описание
id	INT	Первичный ключ
type	ENUM('private', 'group')	Тип диалога
title	VARCHAR(255)	Название (для групп)
created_by	INT	Создатель (FK → users.id)
created_at	DATETIME	Дата создания
updated_at	DATETIME	Дата обновления
Таблица dialogue_users
Поле	Тип	Описание
id	INT	Первичный ключ
dialogue_id	INT	ID диалога (FK → dialogues.id)
user_id	INT	ID пользователя (FK → users.id)
joined_at	DATETIME	Дата присоединения
last_read_at	DATETIME	Время последнего прочтения
Таблица messages
Поле	Тип	Описание
id	INT	Первичный ключ
dialogue_id	INT	ID диалога (FK → dialogues.id)
user_id	INT	Отправитель (FK → users.id)
content	TEXT	Текст сообщения
file_path	VARCHAR(255)	Путь к файлу (если есть)
file_type	VARCHAR(50)	Тип файла (image/video/file)
file_size	INT	Размер файла в байтах
is_read	BOOLEAN	Прочитано
is_deleted	BOOLEAN	Удалено (soft delete)
reply_to	INT	ID цитируемого сообщения
created_at	DATETIME	Время отправки
Таблица message_reactions
Поле	Тип	Описание
id	INT	Первичный ключ
message_id	INT	ID сообщения (FK → messages.id)
user_id	INT	ID пользователя (FK → users.id)
reaction	VARCHAR(10)	Эмодзи реакции
created_at	DATETIME	Время реакции
5. Ограничения на загружаемые файлы
Тип	Макс. размер	Допустимые форматы
Изображения	10 MB	JPEG, PNG, GIF, WebP
Видео	50 MB	MP4, WebM, MOV
Документы	20 MB	PDF, DOC, DOCX, TXT
Аватар	5 MB	JPEG, PNG, WebP
6. Backend (PHP)
Основные контроллеры
Контроллер	Методы	Описание
AuthController	login, register, logout	Аутентификация
ProfileController	show, edit, update, avatar	Профиль и аватар
ChatController	index, show, create, send, delete	Чаты
SearchController	users, messages	Поиск
FileController	upload, download, delete	Файлы
WebSocket сервер (Ratchet)
php
// Основные события WebSocket
- onOpen()      // Подключение пользователя
- onMessage()   // Получение сообщения
- onClose()     // Отключение
- onError()     // Ошибки

// Типы сообщений WebSocket
- 'new_message'    // Новое сообщение
- 'typing'         // Набор текста
- 'read'           // Прочтение сообщений
- 'online_status'  // Статус онлайн
Сервис загрузки файлов
php
FileUploadService:
- uploadAvatar()      // Загрузка аватара
- uploadMessageFile() // Загрузка файла в чат
- validateFile()      // Проверка размера и типа
- generateFileName()  // Безопасное имя файла
- deleteFile()        // Удаление файла
7. Frontend
JavaScript модули
javascript
chat.js          // WebSocket соединение, отправка сообщений
fileUpload.js    // Drag & drop загрузка файлов
notifications.js // Уведомления о новых сообщениях
typing.js        // Индикатор набора текста
search.js        // Поиск пользователей
HTML/CSS особенности
Адаптивный дизайн (мобильная версия)

Поддержка темной темы

Отображение превью файлов

Индикатор загрузки файлов

Drag & drop для отправки файлов

8. API эндпоинты
Метод	URL	Описание
POST	/api/chat/send	Отправить сообщение
POST	/api/chat/upload	Загрузить файл
GET	/api/chat/history/{id}	История сообщений
POST	/api/chat/delete/{id}	Удалить сообщение
POST	/api/user/avatar	Обновить аватар
GET	/api/users/search	Поиск пользователей
POST	/api/chat/create	Создать диалог
POST	/api/chat/typing	Индикатор набора
9. WebSocket события
От клиента:

json
// Аутентификация
{"type": "auth", "token": "jwt_token"}

// Новое сообщение
{"type": "message", "data": {"dialogue_id": 1, "content": "Привет!"}}

// Набор текста
{"type": "typing", "data": {"dialogue_id": 1, "is_typing": true}}

// Прочтение
{"type": "read", "data": {"dialogue_id": 1, "message_ids": [1,2,3]}}
От сервера:

json
// Новое сообщение
{"type": "new_message", "data": {"id": 1, "user": {...}, "content": "..."}}

// Статус набора
{"type": "typing", "data": {"user_id": 2, "is_typing": true}}

// Онлайн статус
{"type": "user_online", "data": {"user_id": 2}}

// Файл загружен
{"type": "file_uploaded", "data": {"message_id": 1, "file_url": "..."}}

11. Технологический стек
Компонент	Технология
Backend	PHP 8.2+ / Symfony 7 / laravel
WebSocket	Ratchet / Workerman
Database	PostgreSQL
Frontend	HTML5, CSS3, JavaScript
File Storage	Local filesystem (с ограничениями)
Async	Fetch API + WebSocket
DevOps	Docker, Docker Compose
VCS	Git (GitHub)
12. Безопасность
Аутентификация: JWT токены для WebSocket

Пароли: password_hash() / password_verify()

Файлы: Проверка MIME типа, ограничение размера

XSS: htmlspecialchars() + Content Security Policy

SQL-инъекции: Подготовленные запросы (Doctrine)

CSRF: Токены в формах

Валидация: Все входные данные

13. Примеры кода
Загрузка файла с ограничениями
php
class FileUploadService
{
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;  // 10 MB
    private const MAX_VIDEO_SIZE = 50 * 1024 * 1024;  // 50 MB
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;   // 20 MB
    
    private const ALLOWED_IMAGES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VIDEOS = ['video/mp4', 'video/webm', 'video/quicktime'];
    private const ALLOWED_FILES = ['application/pdf', 'application/msword', 'text/plain'];
    
    public function upload(UploadedFile $file, string $type): string
    {
        $this->validate($file, $type);
        $filename = $this->generateSecureFilename($file);
        $file->move($this->getUploadPath($type), $filename);
        
        return $filename;
    }
    
    private function validate(UploadedFile $file, string $type): void
    {
        $maxSize = match($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            default => self::MAX_FILE_SIZE
        };
        
        if ($file->getSize() > $maxSize) {
            throw new \Exception("Файл слишком большой");
        }
        
        // Валидация MIME типа
        // ...
    }
}
14. Скриншоты (описание)
Главный экран:

Список диалогов слева

Окно чата справа

Поле ввода сообщения с кнопкой загрузки файлов

Статусы онлайн/офлайн

Профиль:

Аватар с возможностью загрузки

Информация о пользователе

Кнопка начать диалог

Групповой чат:

Список участников

Название группы

Возможность добавить участников

15. Заключение
ItisGram — это полноценный учебный проект, который покрывает ключевые технологии современной веб-разработки:

WebSocket (самое востребованное)

Работа с файлами и медиа

Безопасность

Базы данных

Frontend взаимодействие

Проект реалистичен для выполнения за семестр и дает отличный материал для портфолио.

