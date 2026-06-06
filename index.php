<?php
require __DIR__ . '/config.php';

$user = current_user();
if ($user) {
    redirect_by_role($user);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт хомутов</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
    <main class="center-page">
        <section class="hero-card">
            <p class="badge">Мини-система учёта</p>
            <h1>Учёт выполненных хомутов</h1>
            <p>Работники вводят выполненное количество, админ управляет размерами, складом и отчётами.</p>
            <div class="actions">
                <a class="button primary" href="login.php">Войти</a>
                <a class="button secondary" href="register.php">Регистрация</a>
            </div>
        </section>
    </main>
</body>
</html>
