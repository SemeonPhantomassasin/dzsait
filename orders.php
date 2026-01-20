<?php
require_once __DIR__ . '/inc/header.php';
if (!$user || $user['role'] !== 'buyer') {
    echo '<div class="alert alert-warning">Авторизуйтесь как покупатель.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}

if (isset($_POST['cancel'])) {
    if (cancel_order((int) $_POST['cancel'], $user['id'])) {
        flash('success', 'Заказ отменен.');
    } else {
        flash('danger', 'Отменить можно только новые заказы.');
    }
    $tab = request_tab_id();
    header('Location: /orders.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}

$orders = buyer_orders($user['id']);
?>
<h1 class="h4 mb-3">Мои заказы</h1>
<?php if (!$orders): ?>
    <div class="alert alert-info">Заказов пока нет.</div>
<?php endif; ?>
<?php foreach ($orders as $order): ?>
    <div class="card mb-2">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Заказ #<?=$order['id'];?></strong>
                    <span class="badge bg-secondary ms-2"><?=$order['status'];?></span>
                    <div class="small text-muted"><?=$order['created_at'];?></div>
                </div>
                <div class="fw-bold"><?=number_format($order['total'],0,'.',' ');?> ₽</div>
            </div>
            <ul class="mt-2">
                <?php foreach ($order['items'] as $item): ?>
                    <li><?=$item['name'];?> × <?=$item['quantity'];?> — <?=number_format($item['price']*$item['quantity'],0,'.',' ');?> ₽</li>
                <?php endforeach; ?>
            </ul>
            <?php if ($order['status']==='new'): ?>
                <form method="post" class="mt-2">
                    <button class="btn btn-outline-danger btn-sm" name="cancel" value="<?=$order['id'];?>">Отменить</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

