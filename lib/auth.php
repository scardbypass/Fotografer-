<?php

declare(strict_types=1);

function admin_session_start(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name($config['admin']['session_name'] ?? 'fotografer_admin');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function admin_is_logged_in(array $config): bool
{
    admin_session_start($config);
    return ($_SESSION['admin_authenticated'] ?? false) === true;
}

function admin_require_login(array $config): void
{
    if (!admin_is_logged_in($config)) {
        header('Location: login.php');
        exit;
    }
}

function admin_login(array $config, string $username, string $password): bool
{
    admin_session_start($config);

    $validUser = hash_equals(
        (string) ($config['admin']['username'] ?? ''),
        $username
    );

    $hash = (string) ($config['admin']['password_hash'] ?? '');
    $validPassword = $hash !== '' && password_verify($password, $hash);

    if (!$validUser || !$validPassword) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    return true;
}

function admin_logout(array $config): void
{
    admin_session_start($config);
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
