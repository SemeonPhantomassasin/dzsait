<?php
require_once __DIR__ . '/inc/header.php';
$errors = [];
$form = [
    'full_name' => $_POST['full_name'] ?? '',
    'login' => $_POST['login'] ?? '',
    'email' => $_POST['email'] ?? '',
    'role' => $_POST['role'] ?? 'buyer',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userCreated = validate_registration($_POST, $errors);
    if ($userCreated) {
        set_current_user($userCreated);
        flash('success', 'Регистрация успешна!');
        $tab = request_tab_id();
        header('Location: /' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
}
?>
<h1 class="h4 mb-3">Регистрация</h1>
<form method="post" class="card">
    <div class="card-body">
        <div class="mb-2">
            <label class="form-label">ФИО</label>
            <input class="form-control" name="full_name" value="<?=htmlspecialchars($form['full_name']);?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Логин</label>
            <input class="form-control" name="login" value="<?=htmlspecialchars($form['login']);?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?=htmlspecialchars($form['email']);?>" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Пароль</label>
            <input class="form-control" type="password" name="password" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Повтор пароля</label>
            <input class="form-control" type="password" name="confirm" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Роль</label>
            <select class="form-select" name="role">
                <option value="buyer" <?=$form['role']==='buyer'?'selected':'';?>>Покупатель</option>
                <option value="artisan" <?=$form['role']==='artisan'?'selected':'';?>>Ремесленник</option>
            </select>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" <?=isset($_POST['agree'])?'checked':'';?>>
            <label class="form-check-label" for="agree">Согласен с правилами</label>
        </div>
        <button class="btn btn-primary">Зарегистрироваться</button>
    </div>
</form>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

