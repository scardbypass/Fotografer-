<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method tidak diizinkan']);
    exit;
}

$config = require dirname(__DIR__) . '/lib/bootstrap.php';

require dirname(__DIR__) . '/lib/events.php';

function json_error(int $status, string $message): never
{
    http_response_code($status);

    echo json_encode([
        'ok' => false,
        'error' => $message,
    ]);

    exit;
}

$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($authorization === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    $authorization = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
}

if (!preg_match('/^Bearer\s+(.+)$/', $authorization, $matches)) {
    json_error(401, 'Upload token diperlukan');
}

$uploadToken = $matches[1];
$events = load_events($config);
$event = find_event_by_token($events, $uploadToken);

if ($event === null) {
    json_error(401, 'Upload token tidak valid');
}

$uploadedFile = $_FILES['photo'] ?? null;

if (is_array($uploadedFile) && (int) ($uploadedFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    $uploadError = (int) $uploadedFile['error'];
    json_error(in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 413 : 422, 'Upload foto gagal (kode ' . $uploadError . ')');
}

if (
    !is_array($uploadedFile)
    || empty($uploadedFile['tmp_name'])
    || !is_uploaded_file($uploadedFile['tmp_name'])
) {
    json_error(422, 'File foto diperlukan');
}

$tempPath = $uploadedFile['tmp_name'];
$mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tempPath);

$allowedMimeTypes = $config['upload']['allowed_mime'] ?? [
    'image/jpeg',
    'image/png',
];

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    json_error(415, 'Format foto tidak didukung');
}

$maxSizeMb = (int) ($config['upload']['max_size_mb'] ?? 30);
$maxSizeBytes = $maxSizeMb * 1024 * 1024;
$fileSize = (int) ($uploadedFile['size'] ?? 0);

if ($fileSize <= 0 || $fileSize > $maxSizeBytes) {
    json_error(413, 'Ukuran foto melebihi batas');
}

$eventId = (string) $event['id'];
$eventDirectory = storage_path($config) . '/' . $eventId;

if (!is_dir($eventDirectory)) {
    if (!mkdir($eventDirectory, 0755, true) && !is_dir($eventDirectory)) {
        json_error(500, 'Folder penyimpanan gagal dibuat');
    }
}

$extension = $mimeType === 'image/png' ? 'png' : 'jpg';

$fileName = sprintf(
    '%s_%s.%s',
    date('Ymd_His'),
    bin2hex(random_bytes(5)),
    $extension
);

$destination = $eventDirectory . '/' . $fileName;

if (!move_uploaded_file($tempPath, $destination)) {
    json_error(500, 'Foto gagal disimpan');
}

$events[$eventId]['last_upload'] = date(DATE_ATOM);
$events[$eventId]['upload_count'] =
    (int) ($events[$eventId]['upload_count'] ?? 0) + 1;

save_events($config, $events);

echo json_encode([
    'ok' => true,
    'event' => [
        'id' => $eventId,
        'name' => $event['name'],
    ],
    'file' => [
        'name' => $fileName,
        'size' => filesize($destination),
    ],
]);
