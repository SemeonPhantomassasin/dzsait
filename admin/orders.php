<?php
require_once __DIR__ . '/../inc/functions.php';
require_admin();

$status = $_GET['status'] ?? null;
$orders = admin_orders($status ?: null);
$messages = flashes();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Заказы — Админ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/topbar.php'; ?>
<div class="container py-4">
    <?php foreach ($messages as $type => $items): foreach ($items as $msg): ?>
        <div class="alert alert-<?=$type;?>"><?=$msg;?></div>
    <?php endforeach; endforeach; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">Все заказы</h1>
        <form method="get" class="d-flex gap-2">
            <select class="form-select form-select-sm" name="status">
                <option value="">Все</option>
                <?php foreach (['new'=>'Новый','in_progress'=>'В работе','shipped'=>'Отправлен','cancelled'=>'Отменен'] as $k=>$v): ?>
                    <option value="<?=$k;?>" <?=($status===$k)?'selected':'';?>><?=$v;?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm">Фильтр</button>
        </form>
    </div>
    <?php foreach ($orders as $order): ?>
        <div class="card-soft p-3 mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Заказ #<?=$order['id'];?></strong>
                    <span class="badge bg-secondary"><?=$order['status'];?></span>
                    <div class="small text-muted"><?=$order['created_at'];?> · Покупатель: <?=$order['buyer_name'];?></div>
                </div>
                <div class="fw-bold"><?=number_format($order['total'],0,'.',' ');?> ₽</div>
            </div>
            <ul class="mt-2 mb-0">
                <?php foreach ($order['items'] as $item): ?>
                    <li><?=$item['name'];?> × <?=$item['quantity'];?> — <?=number_format($item['price']*$item['quantity'],0,'.',' ');?> ₽</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>

