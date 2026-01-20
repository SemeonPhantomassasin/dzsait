<?php require_once __DIR__ . '/inc/header.php'; ?>
<div class="hero mb-5">
    <div class="row align-items-center gy-3">
        <div class="col-lg-7">
            <span class="pill mb-2 d-inline-block">Ремесленные товары с проверенными мастерами</span>
            <h1 class="fw-bold mb-3">Уголок Мастера</h1>
            <p class="lead">Каталог изделий ручной работы, прозрачные статусы заказов, честные отзывы и удобная витрина для ремесленников.</p>
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-primary btn-lg" href="/catalog.php">Смотреть каталог</a>
                <a class="btn btn-outline-primary btn-lg" href="/about.php">Как это работает</a>
            </div>
            <div class="d-flex flex-wrap gap-4 mt-4">
                <div class="stat-card">
                    <div class="label">Категории</div>
                    <div class="value">Дерево · Керамика · Текстиль</div>
                </div>
                <div class="stat-card">
                    <div class="label">Опыт мастеров</div>
                    <div class="value">10+ лет в ремесле</div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 text-end">
            <img src="https://images.unsplash.com/photo-1523419400524-fd1d50dc1b48?auto=format&fit=crop&w=720&q=80" class="img-fluid rounded shadow" alt="Мастерская">
        </div>
    </div>
</div>
<div class="mb-4 d-flex justify-content-between align-items-center">
    <h2 class="section-title h4 mb-0">Свежие товары</h2>
    <a class="top-link" href="/catalog.php">В каталог →</a>
</div>
<?php
$products = fetch_products(['sort' => 'new']);
?>
<div class="row g-3 mb-5">
    <?php foreach (array_slice($products, 0, 6) as $product): ?>
        <?php
        global $pdo;
        $mainImg = null;
        if ($product['id']) {
            $imgStmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid AND is_main = 1 LIMIT 1");
            $imgStmt->execute(['pid' => $product['id']]);
            $mainImg = $imgStmt->fetchColumn();
        }
        ?>
        <div class="col-md-4">
            <div class="card product-card h-100">
                <img src="<?=htmlspecialchars($mainImg ?: '/uploads/default.jpg');?>" class="card-img-top" alt="<?=htmlspecialchars($product['name']);?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?=htmlspecialchars($product['name']);?></h5>
                    <p class="text-muted mb-1"><?=$product['studio_name'];?></p>
                    <div class="mb-2"><span class="rating">★</span> <?=number_format($product['avg_rating'], 1);?> (<?=$product['reviews_count'];?>)</div>
                    <div class="fw-bold mb-3"><?=number_format($product['price'], 0, '.', ' ');?> ₽</div>
                    <a class="btn btn-outline-primary mt-auto" href="/product.php?id=<?=$product['id'];?>">Подробнее</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="feature-card h-100">
            <h5 class="mb-2">Отборные мастера</h5>
            <p class="mb-0">Каждый ремесленник ведет публичный профиль, заполняет витрину и отвечает за качество изделий.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card h-100">
            <h5 class="mb-2">Честные отзывы</h5>
            <p class="mb-0">Оценки можно оставить только после отправки заказа, поэтому рейтинги отражают реальный опыт покупателей.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-card h-100">
            <h5 class="mb-2">Прозрачные статусы</h5>
            <p class="mb-0">«Новый» → «Принят в работу» → «Отправлен». Покупатель видит прогресс, мастер управляет заказами из панели.</p>
        </div>
    </div>
</div>

<div class="card-soft p-4">
    <div class="row align-items-center g-3">
        <div class="col-md-7">
            <h3 class="h5 mb-2">Присоединяйтесь как ремесленник</h3>
            <p class="mb-3">Создайте профиль мастерской, добавьте товары с запасами, принимайте заказы и стройте репутацию через отзывы.</p>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge-neutral">Управление товарами</span>
                <span class="badge-neutral">Заказы по статусам</span>
                <span class="badge-neutral">Отзывы покупателей</span>
            </div>
        </div>
        <div class="col-md-5 text-md-end">
            <a class="btn btn-primary" href="/register.php">Стать мастером</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

