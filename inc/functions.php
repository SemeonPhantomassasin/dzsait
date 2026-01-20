<?php
require_once __DIR__ . '/db.php';

// --- "Сессии по вкладкам" ---
// Браузер делит cookie PHPSESSID между вкладками, поэтому обычный $_SESSION['user'] будет общий.
// Решение: каждая вкладка получает свой tab_id (JS хранит его в sessionStorage и прокидывает в ссылки/формы),
// а сервер хранит данные авторизации/корзины в $_SESSION['tabs'][$tabId].
function request_tab_id(): ?string
{
    $raw = $_POST['tab'] ?? $_GET['tab'] ?? null;
    if (!is_string($raw) || $raw === '') {
        return null;
    }
    // немного ограничим формат
    if (!preg_match('/^[A-Za-z0-9_-]{8,64}$/', $raw)) {
        return null;
    }
    return $raw;
}

function tab_id(): string
{
    // Важно: tab_id должен приходить из запроса (GET/POST), а не жить "глобально" в сессии,
    // иначе вкладки снова начнут делить контекст.
    return request_tab_id() ?? 'no-tab';
}

function &tab_session(): array
{
    if (!isset($_SESSION['tabs']) || !is_array($_SESSION['tabs'])) {
        $_SESSION['tabs'] = [];
    }
    $tid = tab_id();
    if (!isset($_SESSION['tabs'][$tid]) || !is_array($_SESSION['tabs'][$tid])) {
        $_SESSION['tabs'][$tid] = [];
    }
    return $_SESSION['tabs'][$tid];
}

function set_current_user(?array $user): void
{
    $tab = &tab_session();
    if ($user) {
        $tab['user'] = $user;
    } else {
        unset($tab['user']);
    }
}

function tab_logout(): void
{
    $tab = &tab_session();
    unset($tab['user']);
    unset($tab['cart']);
}

function tab_cart(): array
{
    $tab = &tab_session();
    return $tab['cart'] ?? [];
}

function set_tab_cart(array $cart): void
{
    $tab = &tab_session();
    $tab['cart'] = $cart;
}

function current_user(): ?array
{
    $tab = &tab_session();
    return $tab['user'] ?? null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_auth($role = null): void
{
    $user = current_user();
    if (!$user) {
        $tab = request_tab_id();
        header('Location: /login.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
    if ($role) {
        $roles = (array) $role;
        if (!in_array($user['role'], $roles, true)) {
            $tab = request_tab_id();
            header('Location: /login.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
            exit;
        }
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        $tab = request_tab_id();
        header('Location: /admin/login.php' . ($tab ? ('?tab=' . urlencode($tab)) : ''));
        exit;
    }
}

function fetch_categories(): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

function fetch_artisans(): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.id, u.full_name, a.studio_name, a.bio, a.photo FROM users u 
        JOIN artisans a ON a.user_id = u.id ORDER BY a.studio_name");
    $stmt->execute();
    return $stmt->fetchAll();
}

function validate_registration(array $data, array &$errors): ?array
{
    global $pdo;

    $full_name = trim($data['full_name'] ?? '');
    $login = trim($data['login'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['confirm'] ?? '';
    $role = $data['role'] ?? '';
    $agree = $data['agree'] ?? '';

    if ($full_name === '') {
        $errors[] = 'Укажите ФИО.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $login)) {
        $errors[] = 'Логин: латиница/цифры 3-20 символов.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Пароль минимум 6 символов.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Пароли не совпадают.';
    }
    if (!in_array($role, ['buyer', 'artisan'], true)) {
        $errors[] = 'Выберите роль.';
    }
    if (!$agree) {
        $errors[] = 'Необходимо согласиться с правилами.';
    }

    $exists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = :login OR email = :email");
    $exists->execute(['login' => $login, 'email' => $email]);
    if ($exists->fetchColumn() > 0) {
        $errors[] = 'Пользователь с таким логином или email уже существует.';
    }

    if ($errors) {
        return null;
    }

    $stmt = $pdo->prepare("INSERT INTO users (full_name, login, email, password_hash, role, created_at) VALUES
        (:full_name, :login, :email, :password_hash, :role, datetime('now'))");
    $stmt->execute([
        'full_name' => $full_name,
        'login' => $login,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        'role' => $role,
    ]);

    $userId = (int) $pdo->lastInsertId();
    if ($role === 'artisan') {
        $pdo->prepare("INSERT INTO artisans (user_id, studio_name, bio, photo) VALUES (:uid, :studio, '', NULL)")
            ->execute(['uid' => $userId, 'studio' => $full_name . ' — мастерская']);
    }

    $user = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $user->execute(['id' => $userId]);
    return $user->fetch();
}

function attempt_login(string $login, string $password, array &$errors): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Неверный логин или пароль.';
        return null;
    }
    if (!empty($user['blocked'])) {
        $errors[] = 'Аккаунт заблокирован администратором.';
        return null;
    }
    return $user;
}

function fetch_products(array $filters = []): array
{
    global $pdo;
    $sql = "SELECT p.*, u.full_name AS artisan_name, a.studio_name,
            IFNULL(AVG(CASE WHEN r.is_visible = 1 THEN r.rating END),0) AS avg_rating,
            COUNT(CASE WHEN r.is_visible = 1 THEN r.id END) AS reviews_count
            FROM products p
            JOIN users u ON u.id = p.artisan_id
            LEFT JOIN artisans a ON a.user_id = p.artisan_id
            LEFT JOIN reviews r ON r.product_id = p.id
            WHERE 1=1";
    $params = [];

    if (empty($filters['include_hidden'])) {
        $sql .= " AND p.stock > 0";
        $sql .= " AND p.is_visible = 1";
    }
    if (!empty($filters['category'])) {
        $sql .= " AND p.category_id = :category";
        $params['category'] = (int) $filters['category'];
    }
    if (!empty($filters['artisan'])) {
        $sql .= " AND a.studio_name LIKE :artisan";
        $params['artisan'] = '%' . $filters['artisan'] . '%';
    }
    if (!empty($filters['price_min'])) {
        $sql .= " AND p.price >= :min_price";
        $params['min_price'] = (float) $filters['price_min'];
    }
    if (!empty($filters['price_max'])) {
        $sql .= " AND p.price <= :max_price";
        $params['max_price'] = (float) $filters['price_max'];
    }

    $sql .= " GROUP BY p.id";

    $sort = $filters['sort'] ?? 'new';
    $orderBy = [
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'rating' => 'avg_rating DESC',
        'name' => 'p.name ASC',
        'new' => 'p.created_at DESC'
    ][$sort] ?? 'p.created_at DESC';

    $sql .= " ORDER BY $orderBy";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_products_paginated(array $filters, int $page, int $perPage, int &$total): array
{
    global $pdo;
    $page = max(1, (int) $page);
    $perPage = max(1, min(60, (int) $perPage));
    $offset = ($page - 1) * $perPage;

    // WHERE-часть должна совпадать с fetch_products (без агрегаций/джойна отзывов для count)
    $where = " WHERE 1=1";
    $params = [];

    if (empty($filters['include_hidden'])) {
        $where .= " AND p.stock > 0";
        $where .= " AND p.is_visible = 1";
    }
    if (!empty($filters['category'])) {
        $where .= " AND p.category_id = :category";
        $params['category'] = (int) $filters['category'];
    }
    if (!empty($filters['artisan'])) {
        $where .= " AND a.studio_name LIKE :artisan";
        $params['artisan'] = '%' . $filters['artisan'] . '%';
    }
    if (!empty($filters['price_min'])) {
        $where .= " AND p.price >= :min_price";
        $params['min_price'] = (float) $filters['price_min'];
    }
    if (!empty($filters['price_max'])) {
        $where .= " AND p.price <= :max_price";
        $params['max_price'] = (float) $filters['price_max'];
    }

    // Total count
    $countSql = "SELECT COUNT(DISTINCT p.id)
        FROM products p
        JOIN users u ON u.id = p.artisan_id
        LEFT JOIN artisans a ON a.user_id = p.artisan_id
        $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Data query (с рейтингами/отзывами)
    $sql = "SELECT p.*, u.full_name AS artisan_name, a.studio_name,
            IFNULL(AVG(CASE WHEN r.is_visible = 1 THEN r.rating END),0) AS avg_rating,
            COUNT(CASE WHEN r.is_visible = 1 THEN r.id END) AS reviews_count
            FROM products p
            JOIN users u ON u.id = p.artisan_id
            LEFT JOIN artisans a ON a.user_id = p.artisan_id
            LEFT JOIN reviews r ON r.product_id = p.id
            $where
            GROUP BY p.id";

    $sort = $filters['sort'] ?? 'new';
    $orderBy = [
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'rating' => 'avg_rating DESC',
        'name' => 'p.name ASC',
        'new' => 'p.created_at DESC'
    ][$sort] ?? 'p.created_at DESC';
    $sql .= " ORDER BY $orderBy";
    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_product(int $id, bool $includeHidden = false): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.full_name AS artisan_name, a.studio_name, a.bio, a.photo,
        IFNULL(AVG(CASE WHEN r.is_visible = 1 THEN r.rating END),0) AS avg_rating,
        COUNT(CASE WHEN r.is_visible = 1 THEN r.id END) AS reviews_count
        FROM products p 
        JOIN users u ON u.id = p.artisan_id
        LEFT JOIN artisans a ON a.user_id = p.artisan_id
        LEFT JOIN reviews r ON r.product_id = p.id
        WHERE p.id = :id " . ($includeHidden ? "" : "AND p.is_visible = 1") . "
        GROUP BY p.id");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
    if (!$product) {
        return null;
    }
    $imgs = $pdo->prepare("SELECT url, is_main FROM product_images WHERE product_id = :pid ORDER BY is_main DESC, id ASC");
    $imgs->execute(['pid' => $id]);
    $allImages = $imgs->fetchAll();
    $product['main_image'] = null;
    $product['images'] = [];
    foreach ($allImages as $img) {
        if ($img['is_main']) {
            $product['main_image'] = $img['url'];
        }
        $product['images'][] = $img['url'];
    }
    if (!$product['main_image'] && !empty($product['images'])) {
        $product['main_image'] = $product['images'][0];
    }
    return $product;
}

function get_reviews(int $productId, bool $includeHidden = false): array
{
    global $pdo;
    $where = $includeHidden ? "" : "AND r.is_visible = 1";
    $stmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.buyer_id WHERE product_id = :id $where ORDER BY r.created_at DESC");
    $stmt->execute(['id' => $productId]);
    return $stmt->fetchAll();
}

function add_to_cart(int $productId, int $qty, array &$errors): void
{
    global $pdo;
    $product = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = :id AND stock > 0 AND is_visible = 1");
    $product->execute(['id' => $productId]);
    $p = $product->fetch();
    if (!$p) {
        $errors[] = 'Товар недоступен.';
        return;
    }
    if ($qty < 1) {
        $errors[] = 'Количество должно быть положительным.';
        return;
    }
    if ($qty > $p['stock']) {
        $errors[] = 'Нельзя добавить больше, чем есть в наличии.';
        return;
    }

    $cart = tab_cart();
    $existing = $cart[$productId]['quantity'] ?? 0;
    if ($existing + $qty > $p['stock']) {
        $errors[] = 'Нельзя добавить больше, чем есть в наличии.';
        return;
    }

    $cart[$productId] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'price' => $p['price'],
        'quantity' => $existing + $qty,
        'stock' => $p['stock'],
    ];
    set_tab_cart($cart);
}

function update_cart_item(int $productId, int $qty): void
{
    $cart = tab_cart();
    if (!isset($cart[$productId])) {
        return;
    }
    if ($qty <= 0) {
        unset($cart[$productId]);
        set_tab_cart($cart);
        return;
    }
    $available = $cart[$productId]['stock'];
    $cart[$productId]['quantity'] = min($qty, $available);
    set_tab_cart($cart);
}

function cart_items(): array
{
    return tab_cart();
}

function cart_total(): float
{
    $total = 0;
    foreach (cart_items() as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function create_order(int $userId, string $passwordConfirm, array &$errors): ?int
{
    global $pdo;
    $cart = cart_items();
    if (!$cart) {
        $errors[] = 'Корзина пуста.';
        return null;
    }
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($passwordConfirm, $hash)) {
        $errors[] = 'Неверный пароль для подтверждения.';
        return null;
    }

    // Проверяем наличие
    foreach ($cart as $item) {
        $available = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
        $available->execute(['id' => $item['id']]);
        $stock = (int) $available->fetchColumn();
        if ($item['quantity'] > $stock) {
            $errors[] = 'Недостаточно товара "' . $item['name'] . '".';
        }
    }
    if ($errors) {
        return null;
    }

    $pdo->beginTransaction();
    try {
        $orderStmt = $pdo->prepare("INSERT INTO orders (buyer_id, status, total, created_at) VALUES (:buyer, 'new', :total, datetime('now'))");
        $orderStmt->execute(['buyer' => $userId, 'total' => cart_total()]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:oid, :pid, :qty, :price)");
        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

        foreach ($cart as $item) {
            $itemStmt->execute([
                'oid' => $orderId,
                'pid' => $item['id'],
                'qty' => $item['quantity'],
                'price' => $item['price'],
            ]);
            $stockStmt->execute(['qty' => $item['quantity'], 'pid' => $item['id']]);
        }

        $pdo->commit();
        set_tab_cart([]);
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        $errors[] = 'Не удалось создать заказ: ' . $e->getMessage();
        return null;
    }
}

function buyer_orders(int $userId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = :id ORDER BY created_at DESC");
    $stmt->execute(['id' => $userId]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid");
        $items->execute(['oid' => $order['id']]);
        $order['items'] = $items->fetchAll();
    }
    return $orders;
}

function cancel_order(int $orderId, int $userId): bool
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = :id AND buyer_id = :uid");
    $stmt->execute(['id' => $orderId, 'uid' => $userId]);
    $status = $stmt->fetchColumn();
    if ($status !== 'new') {
        return false;
    }
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id")->execute(['id' => $orderId]);
    return true;
}

function artisan_products(int $artisanId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE artisan_id = :id ORDER BY created_at DESC");
    $stmt->execute(['id' => $artisanId]);
    return $stmt->fetchAll();
}

function save_product(array $data, int $artisanId, ?int $productId, array &$errors, ?array $mainImage = null, ?array $secondaryImage = null): ?int
{
    global $pdo;
    $name = trim($data['name'] ?? '');
    $price = (float) ($data['price'] ?? 0);
    $stock = max(0, (int) ($data['stock'] ?? 0));
    $category = (int) ($data['category_id'] ?? 0);

    if ($name === '') {
        $errors[] = 'Название обязательно.';
    }
    if ($price <= 0) {
        $errors[] = 'Цена должна быть положительной.';
    }
    if ($category <= 0) {
        $errors[] = 'Выберите категорию.';
    }
    if ($errors) {
        return null;
    }

    if ($productId) {
        $stmt = $pdo->prepare("UPDATE products SET name=:name, description=:description, material=:material, size=:size, price=:price, stock=:stock, category_id=:cat WHERE id=:id AND artisan_id=:aid");
        $stmt->execute([
            'name' => $name,
            'description' => trim($data['description'] ?? ''),
            'material' => trim($data['material'] ?? ''),
            'size' => trim($data['size'] ?? ''),
            'price' => $price,
            'stock' => $stock,
            'cat' => $category,
            'id' => $productId,
            'aid' => $artisanId,
        ]);
        $pid = $productId;
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (artisan_id, category_id, name, description, material, size, price, stock, created_at) VALUES (:aid, :cat, :name, :description, :material, :size, :price, :stock, datetime('now'))");
        $stmt->execute([
            'aid' => $artisanId,
            'cat' => $category,
            'name' => $name,
            'description' => trim($data['description'] ?? ''),
            'material' => trim($data['material'] ?? ''),
            'size' => trim($data['size'] ?? ''),
            'price' => $price,
            'stock' => $stock,
        ]);
        $pid = (int) $pdo->lastInsertId();
    }
    
    // Сохраняем изображения
    if ($mainImage || $secondaryImage) {
        // поддержка старого формата + нового multiple
        $secondaryImages = $secondaryImage;
        save_product_images($pid, $mainImage, $secondaryImages, $errors);
    }
    
    return $pid;
}

function delete_product(int $productId, int $artisanId): void
{
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND artisan_id = :aid");
    $stmt->execute(['id' => $productId, 'aid' => $artisanId]);
}

function artisan_orders(int $artisanId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.id, o.status, o.created_at, o.total, u.full_name as buyer_name
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        JOIN users u ON u.id = o.buyer_id
        WHERE p.artisan_id = :aid
        GROUP BY o.id
        ORDER BY o.created_at DESC");
    $stmt->execute(['aid' => $artisanId]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid AND p.artisan_id = :aid");
        $items->execute(['oid' => $order['id'], 'aid' => $artisanId]);
        $order['items'] = $items->fetchAll();
    }
    return $orders;
}

function update_order_status(int $orderId, int $artisanId, string $newStatus): bool
{
    global $pdo;
    if (!in_array($newStatus, ['in_progress', 'shipped'], true)) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT o.status FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON p.id = oi.product_id
        WHERE o.id = :oid AND p.artisan_id = :aid
        GROUP BY o.id");
    $stmt->execute(['oid' => $orderId, 'aid' => $artisanId]);
    $current = $stmt->fetchColumn();
    if (!$current) {
        return false;
    }
    $allowed = [
        'new' => 'in_progress',
        'in_progress' => 'shipped',
    ];
    if (($allowed[$current] ?? '') !== $newStatus) {
        return false;
    }
    $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id")->execute([
        'status' => $newStatus,
        'id' => $orderId,
    ]);
    return true;
}

function artisan_reviews(int $artisanId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, p.name AS product_name, u.full_name AS buyer_name
        FROM reviews r
        JOIN products p ON p.id = r.product_id
        JOIN users u ON u.id = r.buyer_id
        WHERE p.artisan_id = :aid
        ORDER BY r.created_at DESC");
    $stmt->execute(['aid' => $artisanId]);
    return $stmt->fetchAll();
}

function add_review(int $productId, int $buyerId, int $rating, string $comment, array &$errors): bool
{
    global $pdo;
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Рейтинг от 1 до 5.';
        return false;
    }
    $stmt = $pdo->prepare("SELECT o.status FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.buyer_id = :bid AND oi.product_id = :pid
        ORDER BY o.created_at DESC LIMIT 1");
    $stmt->execute(['bid' => $buyerId, 'pid' => $productId]);
    $status = $stmt->fetchColumn();
    if ($status !== 'shipped') {
        $errors[] = 'Оставлять отзыв можно после получения товара (статус \"Отправлен\").';
        return false;
    }
    $exists = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = :pid AND buyer_id = :bid");
    $exists->execute(['pid' => $productId, 'bid' => $buyerId]);
    if ($exists->fetchColumn() > 0) {
        $errors[] = 'Вы уже оставляли отзыв.';
        return false;
    }
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, buyer_id, rating, comment, is_visible, created_at) VALUES (:pid, :bid, :rating, :comment, 1, datetime('now'))");
    $stmt->execute([
        'pid' => $productId,
        'bid' => $buyerId,
        'rating' => $rating,
        'comment' => trim($comment),
    ]);
    return true;
}

// --- Админ-хелперы ---
function admin_users(): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, full_name, login, email, role, blocked, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function admin_update_user(int $userId, string $role, int $blocked): void
{
    global $pdo;
    if (!in_array($role, ['buyer','artisan','admin'], true)) {
        return;
    }
    $stmt = $pdo->prepare("UPDATE users SET role = :role, blocked = :blocked WHERE id = :id");
    $stmt->execute(['role' => $role, 'blocked' => $blocked, 'id' => $userId]);
}

function admin_categories(): array
{
    return fetch_categories();
}

function admin_save_category(?int $id, string $name): void
{
    global $pdo;
    if (trim($name) === '') return;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $name, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }
}

function admin_delete_category(int $id): void
{
    global $pdo;
    $pdo->prepare("DELETE FROM categories WHERE id = :id")->execute(['id' => $id]);
}

function admin_products(bool $includeHidden = true): array
{
    return fetch_products(['include_hidden' => $includeHidden]);
}

function admin_set_product_visibility(int $productId, int $visible): void
{
    global $pdo;
    $pdo->prepare("UPDATE products SET is_visible = :v WHERE id = :id")->execute(['v' => $visible, 'id' => $productId]);
}

function admin_delete_product(int $productId): void
{
    global $pdo;
    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute(['id' => $productId]);
}

function admin_reviews(bool $includeHidden = true): array
{
    global $pdo;
    $sql = "SELECT r.*, p.name AS product_name, u.full_name AS buyer_name
        FROM reviews r
        JOIN products p ON p.id = r.product_id
        JOIN users u ON u.id = r.buyer_id";
    if (!$includeHidden) {
        $sql .= " WHERE r.is_visible = 1";
    }
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function admin_set_review_visibility(int $reviewId, int $visible): void
{
    global $pdo;
    $pdo->prepare("UPDATE reviews SET is_visible = :v WHERE id = :id")->execute(['v' => $visible, 'id' => $reviewId]);
}

function admin_delete_review(int $reviewId): void
{
    global $pdo;
    $pdo->prepare("DELETE FROM reviews WHERE id = :id")->execute(['id' => $reviewId]);
}

function admin_orders(?string $status = null): array
{
    global $pdo;
    $params = [];
    $where = '';
    if ($status) {
        $where = "WHERE o.status = :status";
        $params['status'] = $status;
    }
    $sql = "SELECT o.*, u.full_name AS buyer_name FROM orders o JOIN users u ON u.id = o.buyer_id $where ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid");
        $items->execute(['oid' => $order['id']]);
        $order['items'] = $items->fetchAll();
    }
    return $orders;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// --- Функции загрузки файлов ---
function upload_file(array $file, string $subdir = '', array &$errors = []): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Ошибка загрузки файла.';
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        $errors[] = 'Разрешены только изображения (JPEG, PNG, GIF, WebP).';
        return null;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Размер файла не должен превышать 5 МБ.';
        return null;
    }
    
    $ext = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg'
    };
    
    $dir = UPLOADS_DIR . ($subdir ? '/' . $subdir : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $filename = uniqid('', true) . '.' . $ext;
    $path = $dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        $errors[] = 'Не удалось сохранить файл.';
        return null;
    }
    
    return '/uploads' . ($subdir ? '/' . $subdir : '') . '/' . $filename;
}

function normalize_uploaded_files(?array $files): array
{
    // Превращает $_FILES['x'] c multiple в список файлов
    if (!$files) {
        return [];
    }
    if (!isset($files['name']) || !is_array($files['name'])) {
        // одиночный файл
        return [$files];
    }
    $out = [];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function save_product_images(int $productId, ?array $mainImage, ?array $secondaryImages, array &$errors): void
{
    global $pdo;
    
    // Удаляем старые изображения
    $stmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid");
    $stmt->execute(['pid' => $productId]);
    $oldImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($oldImages as $oldImg) {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $oldImg;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    $pdo->prepare("DELETE FROM product_images WHERE product_id = :pid")->execute(['pid' => $productId]);
    
    // Загружаем основное фото
    if ($mainImage && $mainImage['error'] === UPLOAD_ERR_OK) {
        $mainUrl = upload_file($mainImage, 'products', $errors);
        if ($mainUrl) {
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, url, is_main) VALUES (:pid, :url, 1)");
            $stmt->execute(['pid' => $productId, 'url' => $mainUrl]);
        }
    }
    
    // Загружаем дополнительные фото (одно или несколько)
    $secondaryList = normalize_uploaded_files($secondaryImages);
    foreach ($secondaryList as $file) {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $secondaryUrl = upload_file($file, 'products', $errors);
        if ($secondaryUrl) {
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, url, is_main) VALUES (:pid, :url, 0)");
            $stmt->execute(['pid' => $productId, 'url' => $secondaryUrl]);
        }
    }
}

// --- Функции профиля покупателя ---
function update_buyer_profile(int $userId, array $data, ?array $avatarFile, array &$errors): bool
{
    global $pdo;
    
    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    if ($fullName === '') {
        $errors[] = 'Укажите ФИО.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email.';
    }
    
    // Проверка уникальности email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
    $stmt->execute(['email' => $email, 'id' => $userId]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Email уже используется другим пользователем.';
    }
    
    // Если указан новый пароль, проверяем текущий
    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            $errors[] = 'Новый пароль должен быть не менее 6 символов.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Пароли не совпадают.';
        }
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($currentPassword, $hash)) {
            $errors[] = 'Неверный текущий пароль.';
        }
    }
    
    if ($errors) {
        return false;
    }
    
    // Обновляем данные
    $updateData = ['full_name' => $fullName, 'email' => $email, 'id' => $userId];
    $sql = "UPDATE users SET full_name = :full_name, email = :email";
    
    if ($newPassword !== '') {
        $sql .= ", password_hash = :password_hash";
        $updateData['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
    }
    
    $sql .= " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateData);
    
    // Загружаем аватар
    if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
        $avatarUrl = upload_file($avatarFile, 'avatars', $errors);
        if ($avatarUrl) {
            // Удаляем старый аватар
            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $oldAvatar = $stmt->fetchColumn();
            if ($oldAvatar) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . $oldAvatar;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->execute(['avatar' => $avatarUrl, 'id' => $userId]);
        }
    }
    
    // Обновляем "сессию вкладки"
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    set_current_user($stmt->fetch());
    
    return true;
}

function buyer_purchased_products(int $userId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT DISTINCT p.*, oi.quantity, oi.price as order_price, o.created_at as purchase_date, o.status
        FROM products p
        JOIN order_items oi ON oi.product_id = p.id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.buyer_id = :uid AND o.status != 'cancelled'
        ORDER BY o.created_at DESC");
    $stmt->execute(['uid' => $userId]);
    $products = $stmt->fetchAll();
    
    foreach ($products as &$product) {
        $imgs = $pdo->prepare("SELECT url FROM product_images WHERE product_id = :pid AND is_main = 1 LIMIT 1");
        $imgs->execute(['pid' => $product['id']]);
        $product['image'] = $imgs->fetchColumn() ?: '/uploads/default.jpg';
    }
    
    return $products;
}

