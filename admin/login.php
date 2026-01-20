<?php
require_once __DIR__ . '/../inc/functions.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userFound = attempt_login($_POST['login'] ?? '', $_POST['password'] ?? '', $errors);
    if ($userFound && $userFound['role'] === 'admin') {
        set_current_user($userFound);
        $tab = request_tab_id();
        header('Location: /admin/index.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    $errors[] = 'Нет доступа.';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Админ — Уголок Мастера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;">
    <div class="container" style="max-width:420px;">
        <div class="card-soft p-4">
            <h1 class="h5 mb-3">Вход в админ-панель</h1>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger"><?=$err;?></div>
            <?php endforeach; ?>
            <form method="post">
                <input type="hidden" name="tab" value="<?=htmlspecialchars($_GET['tab'] ?? '');?>">
                <div class="mb-3">
                    <label class="form-label">Логин</label>
                    <input class="form-control" name="login" required value="<?=htmlspecialchars($_POST['login'] ?? '');?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Пароль</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button class="btn btn-primary w-100">Войти</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var key = 'dz_tab_id';
        var tabId = sessionStorage.getItem(key);
        if (!tabId) {
            tabId = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10)).replace(/\./g, '');
            sessionStorage.setItem(key, tabId);
        }
        var input = document.querySelector('input[name="tab"]');
        if (input) input.value = tabId;
        try {
            var cur = new URL(window.location.href);
            if (!cur.searchParams.has('tab')) {
                cur.searchParams.set('tab', tabId);
                window.history.replaceState(null, '', cur.toString());
            }
        } catch (e) {}
    })();
    </script>
</body>
</html>

