<?php
require __DIR__ . '/config.php';

if (current_user()) {
    redirect_by_role(current_user());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Заполните все поля.';
    } elseif (mb_strlen($password) < 4) {
        $error = 'Пароль должен быть минимум 4 символа.';
    } else {
        $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $role = $usersCount === 0 ? 'admin' : 'worker';

        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            redirect_by_role(current_user());
        } catch (PDOException $exception) {
            $error = 'Такой логин уже существует.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
    <main class="center-page">
        <form class="panel form" method="post">
            <p class="badge">Создать аккаунт</p>
            <h1>Регистрация</h1>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
            <label>Имя
                <input name="name" required>
            </label>
            <label>Логин
                <input name="username" required>
            </label>
            <label>Пароль
                <input name="password" type="password" required>
            </label>
            <button class="button primary" type="submit">Зарегистрироваться</button>
            <a class="small-link" href="login.php">Уже есть аккаунт?</a>
        </form>
    </main>
</body>
</html>
