<?php
require __DIR__ . '/config.php';

if (current_user()) {
    redirect_by_role(current_user());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        redirect_by_role($user);
    } else {
        $error = 'Неверный логин или пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
    <main class="center-page">
        <form class="panel form" method="post">
            <p class="badge">Вход в систему</p>
            <h1>Войти</h1>
            <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
            <label>Логин
                <input name="username" required>
            </label>
            <label>Пароль
                <input name="password" type="password" required>
            </label>
            <button class="button primary" type="submit">Войти</button>
            <a class="small-link" href="register.php">Создать аккаунт</a>
        </form>
    </main>
</body>
</html>
