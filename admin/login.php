<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/lib/auth.php';

if (admin_is_logged_in($config)) {
    header('Location: events.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (admin_login($config, $username, $password)) {
        header('Location: events.php');
        exit;
    }

    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Operator — FOTOGRAFER</title>
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="brand">FOTOGRAFER<small>OPERATOR CONSOLE</small></div>
            <h1>Masuk</h1>
            <p>Kelola event, token tablet, dan akses galeri.</p>

            <?php if ($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <label>
                    Username
                    <input name="username" autocomplete="username" required autofocus>
                </label>
                <label>
                    Password
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button type="submit">Masuk ke operator</button>
            </form>
        </section>
    </main>
</body>
</html>
