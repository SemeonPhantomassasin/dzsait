<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $uid = (int) $_POST['user_id'];
    $role = $_POST['role'] ?? 'buyer';
    $blocked = isset($_POST['blocked']) ? 1 : 0;
    admin_update_user($uid, $role, $blocked);
    flash('success', 'Пользователь обновлён.');
    $tab = request_tab_id();
    header('Location: /admin/users.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}
$messages = flashes();
$users = admin_users();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Пользователи — Админ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <?php foreach ($messages as $type => $items): foreach ($items as $msg): ?>
        <div class="alert alert-<?=$type;?>"><?=$msg;?></div>
    <?php endforeach; endforeach; ?>
    <h1 class="h5 mb-3">Пользователи</h1>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr><th>ID</th><th>Имя</th><th>Логин</th><th>Email</th><th>Роль</th><th>Блок</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?=$u['id'];?></td>
                        <td><?=$u['full_name'];?></td>
                        <td><?=$u['login'];?></td>
                        <td><?=$u['email'];?></td>
                        <td><?=$u['role'];?></td>
                        <td><?=$u['blocked'] ? 'Да' : 'Нет';?></td>
                        <td>
                            <form method="post" class="d-flex flex-wrap gap-2">
                                <input type="hidden" name="user_id" value="<?=$u['id'];?>">
                                <select class="form-select form-select-sm" name="role">
                                    <option value="buyer" <?=$u['role']==='buyer'?'selected':'';?>>Покупатель</option>
                                    <option value="artisan" <?=$u['role']==='artisan'?'selected':'';?>>Ремесленник</option>
                                    <option value="admin" <?=$u['role']==='admin'?'selected':'';?>>Админ</option>
                                </select>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="blocked" value="1" <?=$u['blocked']?'checked':'';?>>
                                    <label class="form-check-label">Блок</label>
                                </div>
                                <button class="btn btn-primary btn-sm">Сохранить</button>
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

