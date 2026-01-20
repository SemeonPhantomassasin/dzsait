<?php
// Базовая конфигурация проекта
date_default_timezone_set('Europe/Moscow');
session_start();

define('DB_PATH', __DIR__ . '/../data/market.db');
define('UPLOADS_DIR', __DIR__ . '/../uploads');

if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}

// Подключаем базу через PDO (SQLite для простоты деплоя)
$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

