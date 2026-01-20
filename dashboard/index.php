<?php
require_once __DIR__ . '/../inc/header.php';
require_auth('artisan');
$products = artisan_products($user['id']);
$orders = artisan_orders($user['id']);
$reviews = artisan_reviews($user['id']);
?>
<h1 class="h4 mb-3">Панель мастера</h1>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Товаров</div>
                <div class="h4"><?=count($products);?></div>
                <a href="/dashboard/products.php">Управлять товарами</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Заказов</div>
                <div class="h4"><?=count($orders);?></div>
                <a href="/dashboard/orders.php">Перейти к заказам</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted">Отзывы</div>
                <div class="h4"><?=count($reviews);?></div>
                <a href="/dashboard/reviews.php">Смотреть отзывы</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>

