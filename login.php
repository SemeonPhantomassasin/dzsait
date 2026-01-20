<?php
require_once __DIR__ . '/inc/header.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userFound = attempt_login($_POST['login'] ?? '', $_POST['password'] ?? '', $errors);
    if ($userFound) {
        set_current_user($userFound);
        flash('success', 'Добро пожаловать!');
        $tab = request_tab_id();
        header('Location: /' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
}
?>
<h1 class="h4 mb-3">Вход</h1>
<form method="post" class="card">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Логин</label>
            <input class="form-control" name="login" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-primary">Войти</button>
    </div>
</form>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

