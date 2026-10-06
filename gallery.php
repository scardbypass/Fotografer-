<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/events.php';

$events = load_events($config);
$eventId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_GET['event'] ?? ''));
$event = $eventId !== '' ? ($events[$eventId] ?? null) : null;

if ($event === null) {
    http_response_code(404);
    $pageTitle = 'Galeri tidak ditemukan';
    $state = 'missing';
    $photos = [];
} elseif (($event['visibility'] ?? 'private') !== 'public') {
    http_response_code(403);
    $pageTitle = 'Galeri privat';
    $state = 'private';
    $photos = [];
} else {
    $pageTitle = (string) ($event['name'] ?? 'Galeri');
    $state = 'public';
    $photos = [];
    $eventDirectory = storage_path($config) . '/' . $eventId;

    if (is_dir($eventDirectory)) {
        $files = glob($eventDirectory . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) ?: [];
        foreach ($files as $filePath) {
            $photos[] = basename($filePath);
        }
        rsort($photos);
    }
}

$safeTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $safeTitle ?> — FOTOGRAFER</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="sitebar">
    <a class="brand brand-link" href="./">FOTOGRAFER<small>INSTANT PHOTO DELIVERY</small></a>
    <nav class="main-nav"><a href="./">Beranda</a><?php if ($state === 'public'): ?><span class="count"><?= count($photos) ?> FOTO</span><?php endif; ?></nav>
</header>

<main class="wrap gallery-wrap">
<?php if ($state !== 'public'): ?>
    <section class="state-page">
        <span class="state-code"><?= $state === 'missing' ? '404' : 'PRIVATE' ?></span>
        <h1><?= $safeTitle ?></h1>
        <p><?= $state === 'missing' ? 'Link galeri tidak valid atau event sudah tidak tersedia.' : 'Galeri ini hanya dapat diakses oleh pihak yang memiliki izin.' ?></p>
        <a class="button primary" href="./">Kembali ke beranda</a>
    </section>
<?php else: ?>
    <section class="gallery-heading">
        <div><span class="eyebrow">Galeri event</span><h1><?= $safeTitle ?></h1><p>Foto terbaru akan muncul otomatis selama event berlangsung.</p></div>
        <span class="badge">PUBLIC</span>
    </section>

    <?php if ($photos): ?>
        <section class="gallery">
        <?php foreach ($photos as $photo): $url = 'photo.php?' . http_build_query(['event' => $eventId, 'file' => $photo]); ?>
            <a class="photo" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="Foto <?= $safeTitle ?>" loading="lazy">
                <span class="photo-open">Buka foto ↗</span>
            </a>
        <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="home-empty"><strong>Belum ada foto.</strong><p>Halaman ini akan memperbarui diri saat foto pertama masuk.</p></section>
    <?php endif; ?>
<?php endif; ?>
</main>

<?php if ($state === 'public'): ?><script>window.setTimeout(function(){window.location.reload();},10000);</script><?php endif; ?>
</body>
</html>
