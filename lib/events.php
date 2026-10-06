<?php

declare(strict_types=1);

function storage_path(array $config): string
{
    $storage = $config['storage'] ?? '';
    $path = is_array($storage) ? ($storage['path'] ?? '') : $storage;

    if (!is_string($path) || trim($path) === '') {
        throw new RuntimeException('Lokasi storage belum dikonfigurasi.');
    }

    return rtrim($path, '/\\');
}

function events_file(array $config): string
{
    return storage_path($config) . '/events.json';
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
    $storageDirectory = storage_path($config);

    if (!is_dir($storageDirectory)) {
        if (!mkdir($storageDirectory, 0755, true) && !is_dir($storageDirectory)) {
            throw new RuntimeException('Folder storage gagal dibuat.');
        }
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


function format_storage_size(int $bytes): string
{
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function event_storage_stats(array $config, string $eventId): array
{
    $directory = storage_path($config) . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $eventId);
    $stats = ['photos' => 0, 'videos' => 0, 'bytes' => 0];
    if (!is_dir($directory)) return $stats;

    foreach (new DirectoryIterator($directory) as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        $size = $file->getSize();
        if (in_array($ext, ['jpg','jpeg','png','webp','heic'], true)) $stats['photos']++;
        elseif (in_array($ext, ['mp4','mov','m4v','webm'], true)) $stats['videos']++;
        else continue;
        $stats['bytes'] += $size;
    }
    return $stats;
}

function total_storage_stats(array $config, array $events): array
{
    $total = ['photos' => 0, 'videos' => 0, 'bytes' => 0];
    foreach ($events as $event) {
        $stats = event_storage_stats($config, (string) ($event['id'] ?? ''));
        $total['photos'] += $stats['photos'];
        $total['videos'] += $stats['videos'];
        $total['bytes'] += $stats['bytes'];
    }
    return $total;
}
