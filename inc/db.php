<?php
require_once __DIR__ . '/config.php';

// Создание схемы и первичных данных
function ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            login TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('buyer','artisan','admin')),
            blocked INTEGER NOT NULL DEFAULT 0,
            avatar TEXT,
            created_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS artisans (
            user_id INTEGER PRIMARY KEY,
            studio_name TEXT NOT NULL,
            bio TEXT,
            photo TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            artisan_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            material TEXT,
            size TEXT,
            price REAL NOT NULL,
            stock INTEGER NOT NULL DEFAULT 0,
            is_visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            FOREIGN KEY(artisan_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS product_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            url TEXT NOT NULL,
            is_main INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            buyer_id INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('new','in_progress','shipped','cancelled')),
            total REAL NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            price REAL NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        );
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            buyer_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
            comment TEXT,
            is_visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            UNIQUE(product_id, buyer_id),
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Мягкие миграции для уже созданной БД
    $pdo->exec("PRAGMA foreign_keys = ON;");

    $addIfMissing = function (string $table, string $column, string $definition) use ($pdo): void {
        $stmt = $pdo->prepare("PRAGMA table_info($table)");
        $stmt->execute();
        $cols = array_column($stmt->fetchAll(), 'name');
        if (!in_array($column, $cols, true)) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $definition");
        }
    };

    $addIfMissing('users', 'blocked', 'blocked INTEGER NOT NULL DEFAULT 0');
    $addIfMissing('users', 'avatar', 'avatar TEXT');
    $addIfMissing('products', 'is_visible', 'is_visible INTEGER NOT NULL DEFAULT 1');
    $addIfMissing('reviews', 'is_visible', 'is_visible INTEGER NOT NULL DEFAULT 1');
    $addIfMissing('product_images', 'is_main', 'is_main INTEGER NOT NULL DEFAULT 0');

    // Если старая таблица users без роли admin — пересоздадим с сохранением данных
    try {
        $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
        if ($sql && strpos($sql, "'admin'") === false) {
            $pdo->exec("PRAGMA foreign_keys = OFF");
            $pdo->beginTransaction();
            try {
                // Удаляем временную таблицу, если она осталась от предыдущей миграции
                $oldExists = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users_old'")->fetchColumn();
                if ($oldExists > 0) {
                    $pdo->exec("DROP TABLE users_old");
                }
                
                // Переименовываем текущую таблицу
                $pdo->exec("ALTER TABLE users RENAME TO users_old");
                
                // Создаем новую таблицу с правильным CHECK
                $pdo->exec("
                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        full_name TEXT NOT NULL,
                        login TEXT NOT NULL UNIQUE,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        role TEXT NOT NULL CHECK(role IN ('buyer','artisan','admin')),
                        blocked INTEGER NOT NULL DEFAULT 0,
                        created_at TEXT NOT NULL
                    )
                ");
                
                // Копируем данные из старой таблицы
                $pdo->exec("
                    INSERT INTO users (id, full_name, login, email, password_hash, role, blocked, created_at)
                    SELECT id, full_name, login, email, password_hash,
                           CASE WHEN role IN ('buyer','artisan','admin') THEN role ELSE 'buyer' END,
                           COALESCE(blocked,0), created_at
                    FROM users_old
                ");
                
                // Удаляем временную таблицу
                $pdo->exec("DROP TABLE users_old");
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                // Если что-то пошло не так, пытаемся восстановить
                $oldExists = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users_old'")->fetchColumn();
                if ($oldExists > 0) {
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS users");
                        $pdo->exec("ALTER TABLE users_old RENAME TO users");
                    } catch (Throwable $e2) {
                        // Игнорируем ошибки восстановления
                    }
                }
                throw $e;
            } finally {
                $pdo->exec("PRAGMA foreign_keys = ON");
            }
        }
    } catch (Throwable $e) {
        // Если таблицы users еще нет, просто продолжаем - она будет создана выше
        if (strpos($e->getMessage(), 'no such table') === false) {
            throw $e;
        }
    }
}

function seed_data(PDO $pdo): void
{
    $hasUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($hasUsers > 0) {
        // гарантируем наличие аккаунта администратора
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = 'admin'");
        $stmt->execute();
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->prepare("INSERT INTO users (full_name, login, email, password_hash, role, created_at) VALUES
                ('Администратор', 'admin', 'admin@example.com', :phash, 'admin', datetime('now'))")
                ->execute(['phash' => password_hash('admin00', PASSWORD_BCRYPT)]);
        }
        return;
    }

    $pdo->exec("INSERT INTO users (full_name, login, email, password_hash, role, created_at) VALUES
        ('Администратор', 'admin', 'admin@example.com', '" . password_hash('admin00', PASSWORD_BCRYPT) . "', 'admin', datetime('now')),
        ('Анна Ковалёва', 'anna', 'anna@example.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'buyer', datetime('now')),
        ('Илья Мастер', 'ilya', 'ilya@example.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'artisan', datetime('now')),
        ('Мария Гончар', 'maria', 'maria@example.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'artisan', datetime('now'))
    ");

    $pdo->exec("INSERT INTO artisans (user_id, studio_name, bio, photo) VALUES
        (3, 'Мастерская Ильи', 'Дерево, кожа, изделия ручной работы.', NULL),
        (4, 'Гончарня Марии', 'Керамика и посуда с характером.', NULL)
    ");

    $pdo->exec("INSERT INTO categories (name) VALUES ('Дерево'), ('Керамика'), ('Текстиль');");

    $pdo->exec("INSERT INTO products (artisan_id, category_id, name, description, material, size, price, stock, created_at) VALUES
        (3, 1, 'Разделочная доска', 'Дуб, ручная работа, покрытие маслом.', 'Дерево', '30x20 см', 2500, 5, datetime('now')),
        (3, 1, 'Кожаный кошелек', 'Натуральная кожа, ручная прошивка.', 'Кожа', '10x8 см', 3200, 3, datetime('now')),
        (4, 2, 'Керамическая кружка', 'Обжиг 1240C, глазурь без свинца.', 'Керамика', '350 мл', 1800, 10, datetime('now'))
    ");

    $pdo->exec("INSERT INTO product_images (product_id, url) VALUES
        (1, 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc'),
        (1, 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6'),
        (2, 'https://images.unsplash.com/photo-1521747116042-5a810fda9664'),
        (3, 'https://images.unsplash.com/photo-1526402462921-75a5f4eaf1b7')
    ");
}

ensure_schema($pdo);
seed_data($pdo);

