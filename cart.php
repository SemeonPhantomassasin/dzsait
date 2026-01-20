<?php
require_once __DIR__ . '/inc/header.php';
if (!$user || $user['role'] !== 'buyer') {
    echo '<div class="alert alert-warning">Только покупатели могут пользоваться корзиной.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['qty'] ?? [] as $pid => $qty) {
            update_cart_item((int) $pid, (int) $qty);
        }
        flash('success', 'Корзина обновлена.');
        $tab = request_tab_id();
        header('Location: /cart.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    if (isset($_POST['remove'])) {
        update_cart_item((int) $_POST['remove'], 0);
        flash('success', 'Товар удален.');
        $tab = request_tab_id();
        header('Location: /cart.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
}

$items = cart_items();
?>
<h1 class="h4 mb-3">Корзина</h1>
<?php if (!$items): ?>
    <div class="alert alert-info">Корзина пуста.</div>
<?php else: ?>
    <form method="post">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?=htmlspecialchars($item['name']);?></td>
                            <td><?=number_format($item['price'],0,'.',' ');?> ₽</td>
                            <td><input class="form-control cart-qty" type="number" name="qty[<?=$item['id'];?>]" min="1" max="<?=$item['stock'];?>" value="<?=$item['quantity'];?>" style="width:100px"></td>
                            <td><?=number_format($item['price'] * $item['quantity'],0,'.',' ');?> ₽</td>
                            <td><button class="btn btn-link text-danger" name="remove" value="<?=$item['id'];?>" type="submit">Удалить</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold h5 mb-0">Итого: <?=number_format(cart_total(),0,'.',' ');?> ₽</div>
            <div>
                <button class="btn btn-secondary" name="update" value="1">Обновить</button>
                <a class="btn btn-primary" href="/checkout.php">Оформить заказ</a>
            </div>
        </div>
    </form>
<?php endif; ?>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

