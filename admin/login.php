<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/auth.php';

if (admin_is_logged_in($config)) {
    header('Location: events.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($config, $_POST['csrf_token'] ?? null);
    $pin = trim((string) ($_POST['pin'] ?? ''));

    if (admin_login($config, $pin)) {
        header('Location: events.php');
        exit;
    }

    $error = 'PIN salah atau akses sementara dikunci.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f4f7ff">
    <title>Admin — FOTOGRAFER</title>
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-card glass-card">
        <div class="login-icon" aria-hidden="true">F</div>
        <div class="brand login-brand">FOTOGRAFER<small>OPERATOR CONSOLE</small></div>
        <h1>Selamat datang</h1>
        <p>Masukkan PIN admin untuk membuka panel fotografer.</p>

        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token($config), ENT_QUOTES, 'UTF-8') ?>">
            <label>
                PIN Admin
                <input class="pin-input" type="password" name="pin" inputmode="numeric" pattern="[0-9]*" autocomplete="current-password" placeholder="••••••" required autofocus>
            </label>
            <button class="glass-primary" type="submit">Buka Panel</button>
        </form>
        <a class="login-back" href="../">← Kembali ke website</a>
    </section>
</main>
</body>
</html>
