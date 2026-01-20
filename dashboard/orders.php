<?php
require_once __DIR__ . '/../inc/header.php';
require_auth('artisan');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['order_id'])) {
    $ok = update_order_status((int) $_POST['order_id'], $user['id'], $_POST['status']);
    flash($ok ? 'success' : 'danger', $ok ? 'Статус обновлен.' : 'Недопустимый переход статуса.');
    $tab = request_tab_id();
    header('Location: /dashboard/orders.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
    exit;
}

$orders = artisan_orders($user['id']);
?>
<h1 class="h4 mb-3">Заказы на мои товары</h1>
<?php foreach ($orders as $order): ?>
    <div class="card mb-2">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>Заказ #<?=$order['id'];?></strong>
                    <span class="badge bg-secondary"><?=$order['status'];?></span>
                    <div class="small text-muted"><?=$order['created_at'];?></div>
                    <div class="small">Покупатель: <?=$order['buyer_name'];?></div>
                </div>
                <div class="fw-bold"><?=number_format($order['total'],0,'.',' ');?> ₽</div>
            </div>
            <ul class="mt-2">
                <?php foreach ($order['items'] as $item): ?>
                    <li><?=$item['name'];?> × <?=$item['quantity'];?></li>
                <?php endforeach; ?>
            </ul>
            <?php if (in_array($order['status'], ['new','in_progress'])): ?>
                <form method="post" class="mt-2 d-flex gap-2">
                    <input type="hidden" name="order_id" value="<?=$order['id'];?>">
                    <?php if ($order['status']==='new'): ?>
                        <button class="btn btn-outline-primary btn-sm" name="status" value="in_progress">Принят в работу</button>
                    <?php elseif ($order['status']==='in_progress'): ?>
                        <button class="btn btn-outline-success btn-sm" name="status" value="shipped">Отправлен</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>

