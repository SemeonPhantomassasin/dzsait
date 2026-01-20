<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        admin_set_product_visibility((int) $_POST['toggle'], (int) $_POST['visible']);
    } elseif (isset($_POST['delete'])) {
        admin_delete_product((int) $_POST['delete']);
    }
    flash('success', 'Обновлено.');
    $tab = request_tab_id();
    header('Location: /admin/products.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}
$messages = flashes();
$products = admin_products(true);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Товары — Админ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <?php foreach ($messages as $type => $items): foreach ($items as $msg): ?>
        <div class="alert alert-<?=$type;?>"><?=$msg;?></div>
    <?php endforeach; endforeach; ?>
    <h1 class="h5 mb-3">Модерация товаров</h1>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Название</th><th>Мастер</th><th>Цена</th><th>Сток</th><th>Видимость</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?=$p['id'];?></td>
                        <td><?=$p['name'];?></td>
                        <td><?=$p['studio_name'];?></td>
                        <td><?=number_format($p['price'],0,'.',' ');?> ₽</td>
                        <td><?=$p['stock'];?></td>
                        <td><?=$p['is_visible'] ? 'Показывается' : 'Скрыт';?></td>
                        <td class="d-flex gap-2">
                            <form method="post">
                                <input type="hidden" name="toggle" value="<?=$p['id'];?>">
                                <input type="hidden" name="visible" value="<?=$p['is_visible'] ? 0 : 1;?>">
                                <button class="btn btn-outline-primary btn-sm"><?=$p['is_visible'] ? 'Скрыть' : 'Показать';?></button>
                            </form>
                            <form method="post">
                                <button class="btn btn-outline-danger btn-sm" name="delete" value="<?=$p['id'];?>">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

