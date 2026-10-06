<?php

declare(strict_types=1);

function events_file(array $config): string
{
    return rtrim($config['storage']['path'], '/') . '/events.json';
}

function load_events(array $config): array
{
    $filePath = events_file($config);

    if (!is_file($filePath)) {
        return [];
    }

    $json = file_get_contents($filePath);

    if ($json === false || $json === '') {
        return [];
    }

    $events = json_decode($json, true);

    return is_array($events) ? $events : [];
}

function save_events(array $config, array $events): void
{
    $storageDirectory = $config['storage']['path'];

    if (!is_dir($storageDirectory)) {
        mkdir($storageDirectory, 0755, true);
    }

    $json = json_encode(
        $events,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        throw new RuntimeException('Event data gagal di-encode.');
    }

    $saved = file_put_contents(
        events_file($config),
        $json,
        LOCK_EX
    );

    if ($saved === false) {
        throw new RuntimeException('Event data gagal disimpan.');
    }
}

function token_hash(string $token): string
{
    return hash('sha256', $token);
}

function new_upload_token(): string
{
    return 'ft_' . bin2hex(random_bytes(24));
}

function find_event_by_token(array $events, string $token): ?array
{
    $incomingHash = token_hash($token);

    foreach ($events as $event) {
        $isActive = (bool) ($event['active'] ?? false);
        $storedHash = (string) ($event['token_hash'] ?? '');

        if (
            $isActive
            && $storedHash !== ''
            && hash_equals($storedHash, $incomingHash)
        ) {
            return $event;
        }
    }

    return null;
}
