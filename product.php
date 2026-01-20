<?php
require_once __DIR__ . '/inc/header.php';
$id = (int) ($_GET['id'] ?? 0);
$product = $id ? get_product($id) : null;
if (!$product) {
    echo '<div class="alert alert-danger">Товар не найден.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $user['role'] === 'buyer') {
    $errors = [];
    if (isset($_POST['add_cart'])) {
        add_to_cart($product['id'], (int) ($_POST['qty'] ?? 1), $errors);
        if (!$errors) {
            flash('success', 'Товар добавлен в корзину.');
            $tab = request_tab_id();
            header('Location: /product.php?id=' . $product['id'] . ($tab ? ('&tab=' . urlencode($tab)) : ''));
            exit;
        }
    }
    if (isset($_POST['review'])) {
        $ok = add_review($product['id'], $user['id'], (int) $_POST['rating'], $_POST['comment'] ?? '', $errors);
        if ($ok) {
            flash('success', 'Спасибо за отзыв!');
            $tab = request_tab_id();
            header('Location: /product.php?id=' . $product['id'] . ($tab ? ('&tab=' . urlencode($tab)) : ''));
            exit;
        }
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
}
$reviews = get_reviews($product['id']);
?>
<div class="row g-4">
    <div class="col-md-6">
        <?php if ($product['main_image']): ?>
            <a href="<?=htmlspecialchars($product['main_image']);?>" class="dz-lightbox" data-dz-lightbox>
                <img class="img-fluid rounded mb-2 shadow-sm" src="<?=htmlspecialchars($product['main_image']);?>" alt="<?=htmlspecialchars($product['name']);?>">
            </a>
        <?php else: ?>
            <a href="/uploads/default.jpg" class="dz-lightbox" data-dz-lightbox>
                <img class="img-fluid rounded mb-2 shadow-sm" src="/uploads/default.jpg" alt="<?=htmlspecialchars($product['name']);?>">
            </a>
        <?php endif; ?>
        <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
            <div class="dz-thumbs">
                <?php foreach (array_slice($product['images'], 1) as $img): ?>
                    <a class="dz-thumb dz-lightbox" href="<?=htmlspecialchars($img);?>" data-dz-lightbox aria-label="Открыть фото товара">
                        <img src="<?=htmlspecialchars($img);?>" alt="">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h1 class="h3"><?=$product['name'];?></h1>
        <div class="mb-2"><span class="rating">★</span> <?=number_format($product['avg_rating'],1);?> (<?=$product['reviews_count'];?>)</div>
        <div class="fw-bold h4 mb-3"><?=number_format($product['price'],0,'.',' ');?> ₽</div>
        <div class="card-soft p-3 mb-3">
            <div class="d-flex justify-content-between">
                <span class="text-muted">Мастер:</span>
                <a href="/profile.php?id=<?=$product['artisan_id'];?>"><?=$product['studio_name'];?></a>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Материал:</span>
                <span><?=htmlspecialchars($product['material']);?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Размеры:</span>
                <span><?=htmlspecialchars($product['size']);?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Наличие:</span>
                <span class="fw-semibold"><?=$product['stock'];?> шт.</span>
            </div>
        </div>
        <p><?=$product['description'];?></p>
        <div class="card-soft p-3 mb-3">
            <strong class="d-block mb-1">Доставка и уход</strong>
            <ul class="mb-0">
                <li>Отправка после статуса «Отправлен», трек-номер в заказе.</li>
                <li>Беречь от влаги, протирать мягкой салфеткой.</li>
                <li>Возврат по договоренности с мастером в течение 7 дней.</li>
            </ul>
        </div>
        <?php if ($user && $user['role']==='buyer'): ?>
            <form method="post" class="d-flex align-items-center gap-2">
                <input type="number" name="qty" value="1" min="1" max="<?=$product['stock'];?>" class="form-control" style="width:120px">
                <button class="btn btn-primary" name="add_cart">Добавить в корзину</button>
            </form>
        <?php else: ?>
            <p class="text-muted">Авторизуйтесь как покупатель, чтобы добавить в корзину.</p>
        <?php endif; ?>
    </div>
</div>
<div class="mt-4">
    <h3 class="h5 mb-3">Отзывы</h3>
    <?php if (empty($reviews)): ?>
        <div class="alert alert-secondary">Пока нет отзывов.</div>
    <?php else: ?>
        <div class="mb-3">
            <strong>Всего отзывов: <?=count($reviews);?></strong>
        </div>
    <?php endif; ?>
    <?php foreach ($reviews as $rev): ?>
        <div class="border rounded p-3 mb-2 bg-white">
            <div class="d-flex justify-content-between">
                <strong><?=$rev['full_name'];?></strong>
                <span class="rating">★ <?=$rev['rating'];?></span>
            </div>
            <div class="small text-muted mb-1"><?=$rev['created_at'];?></div>
            <div><?=$rev['comment'];?></div>
        </div>
    <?php endforeach; ?>
    <?php if ($user && $user['role']==='buyer'): ?>
        <form class="card mt-3" method="post">
            <div class="card-body">
                <h5 class="card-title">Оставить отзыв</h5>
                <div class="mb-2">
                    <label class="form-label">Оценка</label>
                    <select class="form-select" name="rating">
                        <?php for ($i=5;$i>=1;$i--): ?>
                            <option value="<?=$i;?>"><?=$i;?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Комментарий</label>
                    <textarea class="form-control" name="comment" rows="3"></textarea>
                </div>
                <button class="btn btn-success" name="review">Отправить</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

