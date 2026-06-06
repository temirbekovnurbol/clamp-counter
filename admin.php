<?php
require __DIR__ . '/config.php';

$user = require_admin();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_size') {
        $title = trim($_POST['title'] ?? '');
        $stock = (int) ($_POST['stock'] ?? 0);

        if ($title === '' || $stock < 0) {
            $error = 'Введите размер и склад не меньше нуля.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO clamp_sizes (title, stock) VALUES (?, ?)');
                $stmt->execute([$title, $stock]);
                $message = 'Размер добавлен.';
            } catch (PDOException $exception) {
                $error = 'Такой размер уже существует.';
            }
        }
    }

    if ($action === 'update_stock') {
        $sizeId = (int) ($_POST['size_id'] ?? 0);
        $stock = (int) ($_POST['stock'] ?? 0);

        if ($sizeId <= 0 || $stock < 0) {
            $error = 'Неверные данные склада.';
        } else {
            $stmt = $pdo->prepare('UPDATE clamp_sizes SET stock = ? WHERE id = ?');
            $stmt->execute([$stock, $sizeId]);
            $message = 'Склад обновлён.';
        }
    }

    if ($action === 'dispatch_stock') {
        $sizeId = (int) ($_POST['size_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($sizeId <= 0 || $quantity <= 0) {
            $error = 'Укажите верное количество для отгрузки.';
        } else {
            // Проверяем, есть ли столько на складе
            $stmt = $pdo->prepare('SELECT stock FROM clamp_sizes WHERE id = ?');
            $stmt->execute([$sizeId]);
            $currentStock = (int) ($stmt->fetchColumn() ?? 0);

            if ($quantity > $currentStock) {
                $error = 'На складе недостаточно товара!';
            } else {
                $stmt = $pdo->prepare('UPDATE clamp_sizes SET stock = stock - ? WHERE id = ?');
                $stmt->execute([$quantity, $sizeId]);
                $message = 'Товар отгружен (вычтено ' . $quantity . ' шт.).';
            }
        }
    }
}

$sizes = $pdo->query('SELECT * FROM clamp_sizes ORDER BY title')->fetchAll();
$users = $pdo->query('SELECT id, name, username, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$entries = $pdo->query("SELECT work_entries.*, users.name AS user_name, clamp_sizes.title AS size_title, DATE(work_entries.created_at) AS work_date
    FROM work_entries
    JOIN users ON users.id = work_entries.user_id
    JOIN clamp_sizes ON clamp_sizes.id = work_entries.clamp_size_id
    ORDER BY work_date DESC, work_entries.created_at DESC
    LIMIT 100")->fetchAll();
$totals = $pdo->query("SELECT clamp_sizes.title AS size_title, COALESCE(SUM(work_entries.quantity), 0) AS total_quantity
    FROM clamp_sizes
    LEFT JOIN work_entries ON work_entries.clamp_size_id = clamp_sizes.id
    GROUP BY clamp_sizes.id
    ORDER BY clamp_sizes.title")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>Админ-панель</strong>
            <span><?= e($user['name']) ?> · <?= e($user['role']) ?></span>
        </div>
        <nav>
            <a href="worker.php">Кабинет</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </header>

    <main class="layout admin-layout">
        <section class="panel">
            <p class="badge">Склад</p>
            <h1>Размеры хомутов</h1>
            <?php if ($message): ?><div class="success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

            <form class="form inline-form" method="post">
                <input type="hidden" name="action" value="add_size">
                <label>Размер
                    <input name="title" placeholder="Например: 20×32" required>
                </label>
                <label>На складе
                    <input name="stock" type="number" min="0" value="0" required>
                </label>
                <button class="button primary" type="submit">Добавить</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Размер</th>
                            <th>Склад</th>
                            <th>Забрали</th>
                            <th>Редактировать</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sizes as $size): ?>
                            <tr>
                                <td><?= e($size['title']) ?></td>
                                <td><?= (int) $size['stock'] ?></td>
                                <td>
                                    <form class="stock-form dispatch-form" method="post">
                                        <input type="hidden" name="action" value="dispatch_stock">
                                        <input type="hidden" name="size_id" value="<?= (int) $size['id'] ?>">
                                        <input name="quantity" type="number" min="1" placeholder="Кол-во">
                                        <button type="submit" class="dispatch-btn">Вычесть</button>
                                    </form>
                                </td>
                                <td>
                                    <form class="stock-form" method="post">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="size_id" value="<?= (int) $size['id'] ?>">
                                        <input name="stock" type="number" min="0" value="<?= (int) $size['stock'] ?>">
                                        <button type="submit">OK</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$sizes): ?>
                            <tr><td colspan="3">Размеров пока нет.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <p class="badge">Итоги</p>
            <h2>Сделано по размерам</h2>
            <div class="cards">
                <?php foreach ($totals as $total): ?>
                    <article class="stat-card">
                        <span><?= e($total['size_title']) ?></span>
                        <strong><?= (int) $total['total_quantity'] ?></strong>
                        <small>штук выполнено</small>
                    </article>
                <?php endforeach; ?>
                <?php if (!$totals): ?><p class="muted">Добавьте размеры, чтобы видеть итоги.</p><?php endif; ?>
            </div>
        </section>

        <section class="panel wide">
            <p class="badge">Отчёт</p>
            <h2>Выполненные работы</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Работник</th>
                            <th>Заголовок</th>
                            <th>Размер</th>
                            <th>Штук</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $currentDate = ''; ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php if ($currentDate !== $entry['work_date']): ?>
                                <?php $currentDate = $entry['work_date']; ?>
                                <tr class="date-row"><td colspan="5">Отчёт за <?= e($currentDate) ?></td></tr>
                            <?php endif; ?>
                            <tr>
                                <td><?= e($entry['created_at']) ?></td>
                                <td><?= e($entry['user_name']) ?></td>
                                <td><?= e($entry['title']) ?></td>
                                <td><?= e($entry['size_title']) ?></td>
                                <td><?= (int) $entry['quantity'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$entries): ?>
                            <tr><td colspan="5">Работ пока нет.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel wide">
            <p class="badge">Пользователи</p>
            <h2>Аккаунты</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $item): ?>
                            <tr>
                                <td><?= e($item['name']) ?></td>
                                <td><?= e($item['username']) ?></td>
                                <td><?= e($item['role']) ?></td>
                                <td><?= e($item['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
