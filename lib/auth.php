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
    if (admin_is_logged_in($config)) {
        return;
    }

    header('Location: login.php');
    exit;
}

function admin_login(array $config, string $pin): bool
{
    admin_session_start($config);

    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        return false;
    }

    $expectedPin = (string) ($config['admin']['pin'] ?? '');
    $validPin = $expectedPin !== '' && hash_equals($expectedPin, $pin);

    if (!$validPin) {
        $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;

        if ($attempts >= 5) {
            $_SESSION['login_locked_until'] = time() + 60;
            $_SESSION['login_attempts'] = 0;
        }

        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['login_locked_until']);

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

function csrf_token(array $config): string
{
    admin_session_start($config);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_validate(array $config, ?string $token): bool
{
    admin_session_start($config);

    $expected = (string) ($_SESSION['csrf_token'] ?? '');

    return $expected !== ''
        && is_string($token)
        && hash_equals($expected, $token);
}

function csrf_require(array $config, ?string $token): void
{
    if (csrf_validate($config, $token)) {
        return;
    }

    http_response_code(419);
    exit('Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.');
}
