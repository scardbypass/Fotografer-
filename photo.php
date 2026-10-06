<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';

require __DIR__ . '/lib/events.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$eventId = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '',
    (string) ($_GET['event'] ?? '')
);

$fileName = basename(
    (string) ($_GET['file'] ?? '')
);

$events = load_events($config);
$event = $events[$eventId] ?? null;

if ($event === null) {
    http_response_code(404);
    exit('Not found');
}

if (($event['visibility'] ?? 'private') !== 'public' && !($_SESSION['gallery_access'][$eventId] ?? false)) {
    http_response_code(403);
    exit('Forbidden');
}

if ($eventId === '' || $fileName === '') {
    http_response_code(404);
    exit('Not found');
}

$filePath = storage_path($config)
    . '/'
    . $eventId
    . '/'
    . $fileName;

if (!is_file($filePath)) {
    http_response_code(404);
    exit('Not found');
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

if (!is_string($mimeType) || !in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
    http_response_code(415);
    exit('Unsupported media type');
}

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
}
header('Cache-Control: public, max-age=86400');

readfile($filePath);
