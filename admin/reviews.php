<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle'])) {
        admin_set_review_visibility((int) $_POST['toggle'], (int) $_POST['visible']);
    } elseif (isset($_POST['delete'])) {
        admin_delete_review((int) $_POST['delete']);
    }
    flash('success', 'Обновлено.');
    $tab = request_tab_id();
    header('Location: /admin/reviews.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}
$messages = flashes();
$reviews = admin_reviews(true);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Отзывы — Админ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <?php foreach ($messages as $type => $items): foreach ($items as $msg): ?>
        <div class="alert alert-<?=$type;?>"><?=$msg;?></div>
    <?php endforeach; endforeach; ?>
    <h1 class="h5 mb-3">Отзывы</h1>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Товар</th><th>Покупатель</th><th>Оценка</th><th>Комментарий</th><th>Статус</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td><?=$r['id'];?></td>
                        <td><?=$r['product_name'];?></td>
                        <td><?=$r['buyer_name'];?></td>
                        <td><?=$r['rating'];?></td>
                        <td><?=htmlspecialchars($r['comment']);?></td>
                        <td><?=$r['is_visible'] ? 'Опубликован' : 'Скрыт';?></td>
                        <td class="d-flex gap-2">
                            <form method="post">
                                <input type="hidden" name="toggle" value="<?=$r['id'];?>">
                                <input type="hidden" name="visible" value="<?=$r['is_visible'] ? 0 : 1;?>">
                                <button class="btn btn-outline-primary btn-sm"><?=$r['is_visible'] ? 'Скрыть' : 'Показать';?></button>
                            </form>
                            <form method="post">
                                <button class="btn btn-outline-danger btn-sm" name="delete" value="<?=$r['id'];?>">Удалить</button>
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

