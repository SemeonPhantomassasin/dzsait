<?php
require_once __DIR__ . '/inc/header.php';
$filters = [
    'category' => $_GET['category'] ?? '',
    'artisan' => $_GET['artisan'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'sort' => $_GET['sort'] ?? 'new',
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$total = 0;
$products = fetch_products_paginated($filters, $page, $perPage, $total);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$categories = fetch_categories();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Каталог</h1>
    <form class="d-flex gap-2" method="get">
        <select class="form-select" name="sort">
            <option value="new" <?=$filters['sort']==='new'?'selected':'';?>>По новизне</option>
            <option value="price_asc" <?=$filters['sort']==='price_asc'?'selected':'';?>>Цена ↑</option>
            <option value="price_desc" <?=$filters['sort']==='price_desc'?'selected':'';?>>Цена ↓</option>
            <option value="rating" <?=$filters['sort']==='rating'?'selected':'';?>>По популярности</option>
            <option value="name" <?=$filters['sort']==='name'?'selected':'';?>>По названию</option>
        </select>
        <button class="btn btn-outline-primary" type="submit">Применить</button>
    </form>
</div>
<form class="row g-2 mb-4" method="get">
    <div class="col-md-3">
        <label class="form-label">Категория</label>
        <select class="form-select" name="category">
            <option value="">Все</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?=$cat['id'];?>" <?=$filters['category']==$cat['id']?'selected':'';?>><?=$cat['name'];?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Цена от</label>
        <input class="form-control" name="price_min" value="<?=htmlspecialchars($filters['price_min']);?>" type="number" min="0">
    </div>
    <div class="col-md-3">
        <label class="form-label">Цена до</label>
        <input class="form-control" name="price_max" value="<?=htmlspecialchars($filters['price_max']);?>" type="number" min="0">
    </div>
    <div class="col-md-3">
        <label class="form-label">Мастер</label>
        <input class="form-control" name="artisan" value="<?=htmlspecialchars($filters['artisan']);?>" placeholder="Имя мастерской">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Фильтровать</button>
        <a class="btn btn-link" href="/catalog.php">Сбросить</a>
    </div>
</form>
<?php if (!$products): ?>
    <div class="alert alert-info">Нет товаров по заданным параметрам.</div>
<?php endif; ?>
<div class="row g-3">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4">
            <div class="card product-card h-100">
                <?php
                global $pdo;
                $mainImg = null;
                if ($product['id']) {
                    $imgStmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid AND is_main = 1 LIMIT 1");
                    $imgStmt->execute(['pid' => $product['id']]);
                    $mainImg = $imgStmt->fetchColumn();
                }
                ?>
                <img src="<?=htmlspecialchars($mainImg ?: '/uploads/default.jpg');?>" class="card-img-top" alt="<?=htmlspecialchars($product['name']);?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?=htmlspecialchars($product['name']);?></h5>
                    <p class="text-muted mb-1"><?=htmlspecialchars($product['studio_name']);?></p>
                    <div class="mb-2"><span class="rating">★</span> <?=number_format($product['avg_rating'], 1);?> (<?=$product['reviews_count'];?>)</div>
                    <div class="fw-bold mb-3"><?=number_format($product['price'], 0, '.', ' ');?> ₽</div>
                    <a class="btn btn-outline-primary mt-auto" href="/product.php?id=<?=$product['id'];?>">Подробнее</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
    <?php
        $queryBase = $_GET;
        unset($queryBase['page']);
    ?>
    <nav class="mt-4" aria-label="Пагинация каталога">
        <ul class="pagination justify-content-center flex-wrap">
            <?php
                $prev = max(1, $page - 1);
                $next = min($pages, $page + 1);

                $qsPrev = http_build_query(array_merge($queryBase, ['page' => $prev]));
                $qsNext = http_build_query(array_merge($queryBase, ['page' => $next]));
            ?>
            <li class="page-item <?=$page <= 1 ? 'disabled' : '';?>">
                <a class="page-link" href="/catalog.php?<?=$qsPrev;?>">←</a>
            </li>

            <?php
                $window = 2; // показываем +/-2 страницы вокруг текущей
                $start = max(1, $page - $window);
                $end = min($pages, $page + $window);
                if ($start > 1) {
                    $qs1 = http_build_query(array_merge($queryBase, ['page' => 1]));
                    echo '<li class="page-item"><a class="page-link" href="/catalog.php?' . $qs1 . '">1</a></li>';
                    if ($start > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    }
                }
                for ($p = $start; $p <= $end; $p++) {
                    $qs = http_build_query(array_merge($queryBase, ['page' => $p]));
                    $active = $p === $page ? 'active' : '';
                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="/catalog.php?' . $qs . '">' . $p . '</a></li>';
                }
                if ($end < $pages) {
                    if ($end < $pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    }
                    $qsLast = http_build_query(array_merge($queryBase, ['page' => $pages]));
                    echo '<li class="page-item"><a class="page-link" href="/catalog.php?' . $qsLast . '">' . $pages . '</a></li>';
                }
            ?>

            <li class="page-item <?=$page >= $pages ? 'disabled' : '';?>">
                <a class="page-link" href="/catalog.php?<?=$qsNext;?>">→</a>
            </li>
        </ul>
        <div class="text-center text-muted small">Показано <?=count($products);?> из <?=$total;?> товаров</div>
    </nav>
<?php endif; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

