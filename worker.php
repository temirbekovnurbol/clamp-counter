<?php
require __DIR__ . '/config.php';

$user = require_login();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $clampSizeId = (int) ($_POST['clamp_size_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);

    $stmt = $pdo->prepare('SELECT id FROM clamp_sizes WHERE id = ?');
    $stmt->execute([$clampSizeId]);
    $sizeExists = (bool) $stmt->fetch();

    if ($title === '' || !$sizeExists || $quantity <= 0) {
        $error = 'Выберите размер, введите заголовок и количество больше нуля.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO work_entries (user_id, clamp_size_id, title, quantity) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user['id'], $clampSizeId, $title, $quantity]);

            $stmt = $pdo->prepare('UPDATE clamp_sizes SET stock = stock + ? WHERE id = ?');
            $stmt->execute([$quantity, $clampSizeId]);

            $pdo->commit();
            $message = 'Запись сохранена, склад пополнен.';
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $error = 'Не удалось сохранить запись.';
        }
    }
}

$sizes = $pdo->query('SELECT * FROM clamp_sizes ORDER BY title')->fetchAll();
$stmt = $pdo->prepare('SELECT work_entries.*, clamp_sizes.title AS size_title FROM work_entries JOIN clamp_sizes ON clamp_sizes.id = work_entries.clamp_size_id WHERE user_id = ? ORDER BY work_entries.created_at DESC LIMIT 20');
$stmt->execute([$user['id']]);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет работника</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>Учёт хомутов</strong>
            <span><?= e($user['name']) ?> · <?= e($user['role']) ?></span>
        </div>
        <nav>
            <?php if ($user['role'] === 'admin'): ?><a href="admin.php">Админка</a><?php endif; ?>
            <a href="logout.php">Выйти</a>
        </nav>
    </header>

    <main class="layout">
        <section class="panel">
            <p class="badge">Новая запись</p>
            <h1>Сколько хомутов выполнено?</h1>
            <?php if ($message): ?><div class="success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

            <?php if (!$sizes): ?>
                <p class="muted">Пока нет размеров. Админ должен добавить размеры в админ-панели.</p>
            <?php else: ?>
                <form class="form" method="post">
                    <label>Заголовок / заказ
                        <input name="title" placeholder="Например: Заказ №15" required>
                    </label>
                    <label>Размер хомута
                        <select name="clamp_size_id" required>
                            <option value="">Выберите размер</option>
                            <?php foreach ($sizes as $size): ?>
                                <option value="<?= (int) $size['id'] ?>"><?= e($size['title']) ?> · склад: <?= (int) $size['stock'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Выполнено штук
                        <input name="quantity" type="number" min="1" required>
                    </label>
                    <button class="button primary" type="submit">Сохранить</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="panel">
            <p class="badge">Мои последние записи</p>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Заголовок</th>
                            <th>Размер</th>
                            <th>Штук</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= e($entry['created_at']) ?></td>
                                <td><?= e($entry['title']) ?></td>
                                <td class="size-title"><?= e($entry['size_title']) ?></td>
                                <td><?= (int) $entry['quantity'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$entries): ?>
                            <tr><td colspan="4">Записей пока нет.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
