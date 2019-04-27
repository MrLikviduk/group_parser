<?php
    define('CONFIRMATION_TOKEN', ''); // Токен, который понадобится вывести, когда будешь подтверждать свой сервер, чтобы на сервер события приходили (если не хочешь, чтобы в телегу уведомления о новых подписчиках приходили, то не надо)
    define('ACCESS_TOKEN', ''); // Токен ГРУППЫ
    define('SECRET_KEY', ''); // Секретный ключ рядом с confirmation token (опять же, не надо, если уведомления в телегу не хочешь привязывать)
    define('VERSION', ''); // Версия API VK
    define('GROUP_ID', ''); // ID группы
    define('ADMIN_ID', ''); // Твой ID
    define('USER_ACCESS_TOKEN', ''); // Токен ЮЗЕРА (Необходимые права: offline, wall, photos)

    define('ACCESS_KEY_TO_PANEL', ''); // Пароль для доступа в админ-панель

    // Инфа для доступа к базе
    define('DB_HOST', '');
    define('DB_NAME', '');
    define('DB_USERNAME', '');
    define('DB_PASSWORD', '');

    // Телега тупо
    define('TELEGRAM_ACCESS_TOKEN', '');
    define('TELEGRAM_CHAT_ID', '');