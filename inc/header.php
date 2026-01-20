<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
$messages = flashes();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Уголок Мастера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="topbar">
    <div class="container d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a class="brand d-flex align-items-center gap-2" href="/">
                <img src="/logo.png" alt="Уголок Мастера" style="height:32px; width:auto;">
                <span>Уголок Мастера</span>
            </a>
            <span class="topbar-note">Маркетплейс ручной работы</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a class="top-link" href="/catalog.php">Каталог</a>
            <a class="top-link" href="/masters.php">Наши мастера</a>
            <a class="top-link" href="/about.php">О проекте</a>
            <?php if ($user): ?>
                <?php if ($user['role'] === 'buyer'): ?>
                    <a class="top-link" href="/cart.php">Корзина</a>
                    <a class="top-link" href="/orders.php">Мои заказы</a>
                    <a class="top-link" href="/account.php">Личный кабинет</a>
                <?php elseif ($user['role'] === 'artisan'): ?>
                    <a class="top-link" href="/dashboard/index.php">Панель мастера</a>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <a class="top-link fw-bold" href="/admin/index.php">Админ</a>
                <?php endif; ?>
                <span class="top-user"><?=$user['full_name'];?></span>
                <a class="btn btn-outline-light btn-sm" href="/logout.php">Выход</a>
            <?php else: ?>
                <a class="btn btn-outline-light btn-sm" href="/login.php">Войти</a>
                <a class="btn btn-light btn-sm" href="/register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container page-container mt-4">
    <?php foreach ($messages as $type => $items): ?>
        <?php foreach ($items as $msg): ?>
            <div class="alert alert-<?=$type;?> mb-3" role="alert">
                <?=$msg;?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

