<?php
require_once __DIR__ . '/../inc/header.php';
require_auth('artisan');

if (isset($_POST['delete'])) {
    delete_product((int) $_POST['delete'], $user['id']);
    flash('success', 'Товар удален.');
    $tab = request_tab_id();
    header('Location: /dashboard/products.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}

$products = artisan_products($user['id']);
$categories = fetch_categories();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Мои товары</h1>
    <a class="btn btn-primary" href="/dashboard/product_edit.php">Добавить товар</a>
</div>
<table class="table table-striped">
    <thead>
        <tr><th>Название</th><th>Категория</th><th>Цена</th><th>Запас</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?=$p['name'];?></td>
                <td><?php
                    $cat = array_filter($categories, fn($c) => $c['id']==$p['category_id']);
                    echo $cat ? array_values($cat)[0]['name'] : '';
                ?></td>
                <td><?=number_format($p['price'],0,'.',' ');?> ₽</td>
                <td><?=$p['stock'];?></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="/dashboard/product_edit.php?id=<?=$p['id'];?>">Редактировать</a>
                    <form method="post" style="display:inline">
                        <button class="btn btn-sm btn-outline-danger" name="delete" value="<?=$p['id'];?>">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>

