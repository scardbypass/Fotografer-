<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

require __DIR__ . '/lib/events.php';

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

if (
    $event === null
    || ($event['visibility'] ?? 'private') !== 'public'
) {
    http_response_code(403);
    exit('Forbidden');
}

if ($eventId === '' || $fileName === '') {
    http_response_code(404);
    exit('Not found');
}

$filePath = $config['storage']
    . '/'
    . $eventId
    . '/'
    . $fileName;

if (!is_file($filePath)) {
    http_response_code(404);
    exit('Not found');
}

$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=86400');

readfile($filePath);
