<?php
require_once __DIR__ . '/../inc/header.php';
require_auth('artisan');
$reviews = artisan_reviews($user['id']);
?>
<h1 class="h4 mb-3">Отзывы на мои товары</h1>
<?php if (!$reviews): ?>
    <div class="alert alert-secondary">Пока нет отзывов.</div>
<?php endif; ?>
<?php foreach ($reviews as $rev): ?>
    <div class="card mb-2">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="fw-bold"><?=$rev['product_name'];?></div>
                    <div class="small text-muted">Покупатель: <?=$rev['buyer_name'];?></div>
                </div>
                <span class="rating">★ <?=$rev['rating'];?></span>
            </div>
            <div class="small text-muted mb-1"><?=$rev['created_at'];?></div>
            <div><?=$rev['comment'];?></div>
        </div>
    </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>

