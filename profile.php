<?php
require_once __DIR__ . '/inc/header.php';
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT u.full_name, a.* FROM artisans a JOIN users u ON u.id = a.user_id WHERE a.user_id = :id");
$stmt->execute(['id' => $id]);
$artisan = $stmt->fetch();
if (!$artisan) {
    echo '<div class="alert alert-danger">Мастер не найден.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h3 class="h5"><?=$artisan['studio_name'];?></h3>
                <p class="text-muted"><?=$artisan['full_name'];?></p>
                <p><?=$artisan['bio'];?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <h4 class="h6 mb-2">Товары мастера</h4>
        <?php $products = artisan_products($id); ?>
        <div class="row g-2">
            <?php foreach ($products as $p): ?>
                <?php
                    $imgStmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid AND is_main = 1 LIMIT 1");
                    $imgStmt->execute(['pid' => $p['id']]);
                    $mainImg = $imgStmt->fetchColumn();
                ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <img src="<?=htmlspecialchars($mainImg ?: '/uploads/default.jpg');?>" class="card-img-top" alt="<?=htmlspecialchars($p['name']);?>">
                        <div class="card-body">
                            <h5 class="card-title"><?=$p['name'];?></h5>
                            <div class="fw-bold mb-2"><?=number_format($p['price'],0,'.',' ');?> ₽</div>
                            <a class="btn btn-outline-primary" href="/product.php?id=<?=$p['id'];?>">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

