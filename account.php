<?php
require_once __DIR__ . '/inc/header.php';
if (!$user || $user['role'] !== 'buyer') {
    echo '<div class="alert alert-warning">Только покупатели могут использовать личный кабинет.</div>';
    require_once __DIR__ . '/inc/footer.php';
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avatarFile = $_FILES['avatar'] ?? null;
    if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_NO_FILE) {
        $avatarFile = null;
    }
    
    if (update_buyer_profile($user['id'], $_POST, $avatarFile, $errors)) {
        flash('success', 'Профиль успешно обновлен!');
        $tab = request_tab_id();
        header('Location: /account.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    foreach ($errors as $err) {
        flash('danger', $err);
    }
}

// Обновляем данные пользователя из БД
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
$userData = $stmt->fetch();
$purchasedProducts = buyer_purchased_products($user['id']);
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card card-soft">
            <div class="card-body text-center">
                <img src="<?=htmlspecialchars($userData['avatar'] ?? '/uploads/default-avatar.png');?>" 
                     alt="Аватар" 
                     class="rounded-circle mb-3" 
                     style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #e5e7eb;">
                <h5 class="card-title"><?=htmlspecialchars($userData['full_name']);?></h5>
                <p class="text-muted mb-0">Покупатель</p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-soft mb-4">
            <div class="card-body">
                <h4 class="h5 mb-3">Редактирование профиля</h4>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Аватар</label>
                        <input type="file" class="form-control" name="avatar" accept="image/*">
                        <div class="form-text">JPEG, PNG, GIF или WebP, до 5 МБ</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="full_name" 
                               value="<?=htmlspecialchars($userData['full_name']);?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?=htmlspecialchars($userData['email']);?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Логин</label>
                        <input type="text" class="form-control" value="<?=htmlspecialchars($userData['login']);?>" disabled>
                        <div class="form-text">Логин нельзя изменить</div>
                    </div>
                    <hr>
                    <h5 class="h6 mb-3">Изменение пароля</h5>
                    <div class="mb-3">
                        <label class="form-label">Текущий пароль</label>
                        <input type="password" class="form-control" name="current_password">
                        <div class="form-text">Оставьте пустым, если не хотите менять пароль</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Новый пароль</label>
                        <input type="password" class="form-control" name="new_password" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Подтверждение пароля</label>
                        <input type="password" class="form-control" name="confirm_password">
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>
        
        <div class="card card-soft">
            <div class="card-body">
                <h4 class="h5 mb-3">Купленные товары</h4>
                <?php if (empty($purchasedProducts)): ?>
                    <div class="alert alert-info">Вы еще не покупали товары.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($purchasedProducts as $product): ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <img src="<?=htmlspecialchars($product['image']);?>" 
                                         class="card-img-top" 
                                         style="height: 150px; object-fit: cover;"
                                         alt="<?=htmlspecialchars($product['name']);?>">
                                    <div class="card-body">
                                        <h6 class="card-title"><?=htmlspecialchars($product['name']);?></h6>
                                        <p class="text-muted small mb-1">
                                            Куплено: <?=date('d.m.Y', strtotime($product['purchase_date']));?>
                                        </p>
                                        <p class="text-muted small mb-1">
                                            Количество: <?=$product['quantity'];?> шт.
                                        </p>
                                        <p class="fw-bold mb-0">
                                            <?=number_format($product['order_price'] * $product['quantity'], 0, '.', ' ');?> ₽
                                        </p>
                                        <span class="badge bg-<?=$product['status']==='shipped'?'success':'secondary';?>">
                                            <?=$product['status']==='shipped'?'Получено':'В обработке';?>
                                        </span>
                                        <div class="mt-2">
                                            <a href="/product.php?id=<?=$product['id'];?>" class="btn btn-sm btn-outline-primary">Подробнее</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>

