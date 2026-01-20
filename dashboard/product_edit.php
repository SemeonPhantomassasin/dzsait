<?php
require_once __DIR__ . '/../inc/header.php';
require_auth('artisan');
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$product = null;
if ($id) {
    $all = artisan_products($user['id']);
    foreach ($all as $p) {
        if ($p['id'] === $id) { $product = $p; break; }
    }
    if (!$product) {
        echo '<div class="alert alert-danger">Товар не найден.</div>';
        require_once __DIR__ . '/../inc/footer.php';
        exit;
    }
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mainImage = $_FILES['main_image'] ?? null;
    $secondaryImages = $_FILES['secondary_images'] ?? null;
    if ($mainImage && $mainImage['error'] === UPLOAD_ERR_NO_FILE) {
        $mainImage = null;
    }
    // Для multiple: error будет массивом
    if ($secondaryImages && isset($secondaryImages['error']) && !is_array($secondaryImages['error']) && $secondaryImages['error'] === UPLOAD_ERR_NO_FILE) {
        $secondaryImages = null;
    }
    
    $savedId = save_product($_POST, $user['id'], $id, $errors, $mainImage, $secondaryImages);
    if ($savedId) {
        flash('success', 'Товар сохранен.');
        $tab = request_tab_id();
        header('Location: /dashboard/products.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    foreach ($errors as $err) { flash('danger', $err); }
}

// Получаем текущие изображения товара
global $pdo;
$currentImages = [];
if ($product) {
    $imgStmt = $pdo->prepare("SELECT url, is_main FROM product_images WHERE product_id = :pid ORDER BY is_main DESC");
    $imgStmt->execute(['pid' => $product['id']]);
    $currentImages = $imgStmt->fetchAll();
}
$categories = fetch_categories();
?>
<h1 class="h4 mb-3"><?=$product?'Редактировать':'Добавить';?> товар</h1>
<form method="post" enctype="multipart/form-data" class="card">
    <div class="card-body">
        <div class="mb-2">
            <label class="form-label">Название</label>
            <input class="form-control" name="name" value="<?=htmlspecialchars($product['name'] ?? '');?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Категория</label>
            <select class="form-select" name="category_id" required>
                <option value="">Выберите</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?=$cat['id'];?>" <?=($product['category_id'] ?? '')==$cat['id']?'selected':'';?>><?=$cat['name'];?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Описание</label>
            <textarea class="form-control" name="description" rows="3"><?=htmlspecialchars($product['description'] ?? '');?></textarea>
        </div>
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label">Материал</label>
                <input class="form-control" name="material" value="<?=htmlspecialchars($product['material'] ?? '');?>">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Размер</label>
                <input class="form-control" name="size" value="<?=htmlspecialchars($product['size'] ?? '');?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Цена</label>
                <input class="form-control" type="number" step="0.01" name="price" value="<?=htmlspecialchars($product['price'] ?? '');?>" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Запас</label>
                <input class="form-control" type="number" name="stock" value="<?=htmlspecialchars($product['stock'] ?? '');?>" min="0" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Основное фото</label>
            <?php if ($product): ?>
                <?php foreach ($currentImages as $img): ?>
                    <?php if ($img['is_main']): ?>
                        <div class="mb-2">
                            <img src="<?=htmlspecialchars($img['url']);?>" alt="Текущее фото" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                            <div class="small text-muted">Текущее основное фото</div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="file" class="form-control" name="main_image" accept="image/*">
            <div class="form-text">JPEG, PNG, GIF или WebP, до 5 МБ</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Дополнительные фото</label>
            <?php if ($product): ?>
                <?php foreach ($currentImages as $img): ?>
                    <?php if (!$img['is_main']): ?>
                        <div class="mb-2">
                            <img src="<?=htmlspecialchars($img['url']);?>" alt="Текущее фото" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                            <div class="small text-muted">Текущее дополнительное фото</div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="file" class="form-control" name="secondary_images[]" accept="image/*" multiple>
            <div class="form-text">Опционально. Можно выбрать несколько файлов. JPEG, PNG, GIF или WebP, до 5 МБ каждый</div>
        </div>
        <button class="btn btn-success">Сохранить</button>
        <a class="btn btn-link" href="/dashboard/products.php">Назад</a>
    </div>
</form>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>

