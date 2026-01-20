<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        admin_delete_category((int) $_POST['delete']);
    } else {
        $id = $_POST['id'] ? (int) $_POST['id'] : null;
        admin_save_category($id, $_POST['name'] ?? '');
    }
    flash('success', 'Категории обновлены.');
    $tab = request_tab_id();
    header('Location: /admin/categories.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}
$messages = flashes();
$categories = admin_categories();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Категории — Админ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <?php foreach ($messages as $type => $items): foreach ($items as $msg): ?>
        <div class="alert alert-<?=$type;?>"><?=$msg;?></div>
    <?php endforeach; endforeach; ?>
    <h1 class="h5 mb-3">Категории</h1>
    <div class="row g-3">
        <div class="col-md-6">
            <form method="post" class="card-soft p-3">
                <h6 class="mb-3">Добавить категорию</h6>
                <input type="hidden" name="id" value="">
                <div class="mb-3">
                    <label class="form-label">Название</label>
                    <input class="form-control" name="name" required>
                </div>
                <button class="btn btn-primary">Создать</button>
            </form>
        </div>
        <div class="col-md-6">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>ID</th><th>Название</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?=$cat['id'];?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="id" value="<?=$cat['id'];?>">
                                        <input class="form-control form-control-sm" name="name" value="<?=htmlspecialchars($cat['name']);?>">
                                        <button class="btn btn-outline-primary btn-sm">Сохранить</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post">
                                        <button class="btn btn-outline-danger btn-sm" name="delete" value="<?=$cat['id'];?>">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>

