<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Админ панель — Уголок Мастера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <h1 class="h4 mb-4">Админ-панель</h1>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="feature-card">
                <h5>Пользователи</h5>
                <p class="mb-0">Роли, блокировки, управление доступом.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <h5>Категории</h5>
                <p class="mb-0">Добавление, переименование, удаление категорий каталога.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <h5>Модерация</h5>
                <p class="mb-0">Скрытие/удаление товаров и отзывов, просмотр заказов.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>

