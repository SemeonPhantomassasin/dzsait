<?php
require_once __DIR__ . '/inc/header.php';
if (!$user || $user['role'] !== 'buyer') {
    echo '<div class="alert alert-warning">Авторизуйтесь как покупатель.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}
$items = cart_items();
if (!$items) {
    echo '<div class="alert alert-info">Корзина пуста.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = create_order($user['id'], $_POST['password_confirm'] ?? '', $errors);
    if ($orderId) {
        flash('success', 'Заказ #' . $orderId . ' оформлен!');
        $tab = request_tab_id();
        header('Location: /orders.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
}
?>
<h1 class="h4 mb-3">Оформление заказа</h1>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Ваш заказ</h5>
        <ul class="list-group list-group-flush">
            <?php foreach ($items as $item): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?=$item['name'];?> × <?=$item['quantity'];?></span>
                    <span><?=number_format($item['price'] * $item['quantity'],0,'.',' ');?> ₽</span>
                </li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between fw-bold">
                <span>Итого</span>
                <span><?=number_format(cart_total(),0,'.',' ');?> ₽</span>
            </li>
        </ul>
    </div>
 </div>
<form method="post" class="card">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Подтверждение паролем</label>
            <input class="form-control" type="password" name="password_confirm" required>
            <div class="form-text">Введите пароль аккаунта для подтверждения заказа.</div>
        </div>
        <button class="btn btn-success">Подтвердить заказ</button>
    </div>
</form>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

