<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';

require __DIR__ . '/lib/events.php';

$events = load_events($config);

$eventId = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '',
    (string) ($_GET['event'] ?? '')
);

$event = $events[$eventId] ?? null;

if ($event === null) {
    http_response_code(404);
    exit('Galeri tidak ditemukan');
}

$isPrivate = ($event['visibility'] ?? 'private') !== 'public';
$photos = [];

if (!$isPrivate) {
    $eventDirectory = $config['storage'] . '/' . $eventId;

    if (is_dir($eventDirectory)) {
        $pattern = $eventDirectory . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}';
        $files = glob($pattern, GLOB_BRACE) ?: [];

        foreach ($files as $filePath) {
            $photos[] = basename($filePath);
        }

        rsort($photos);
    }
}

$eventName = htmlspecialchars(
    (string) $event['name'],
    ENT_QUOTES,
    'UTF-8'
);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $eventName ?> — FOTOGRAFER</title>

    <link rel="stylesheet" href="assets/app.css">
</head>
<body>

<header class="sitebar">
    <div class="brand">
        FOTOGRAFER
        <small>INSTANT PHOTO DELIVERY</small>
    </div>

    <?php if (!$isPrivate): ?>
        <span class="count">
            <?= count($photos) ?> FOTO
        </span>
    <?php endif; ?>
</header>

<main class="wrap">
    <?php if ($isPrivate): ?>
        <section class="empty">
            <span class="badge private">PRIVATE</span>

            <h1>Galeri privat</h1>

            <p>
                Galeri ini tidak tersedia untuk akses publik.
            </p>
        </section>
    <?php else: ?>
        <section class="topline">
            <div>
                <div class="eyebrow">Galeri acara</div>

                <h1><?= $eventName ?></h1>

                <p>
                    Foto terbaru muncul otomatis saat fotografer mengirimkannya.
                </p>
            </div>

            <span class="badge">PUBLIC</span>
        </section>

        <?php if ($photos): ?>
            <section class="gallery">
                <?php foreach ($photos as $photo): ?>
                    <?php
                    $photoUrl = 'photo.php?' . http_build_query([
                        'event' => $eventId,
                        'file' => $photo,
                    ]);
                    ?>

                    <a
                        class="photo"
                        href="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <img
                            src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                            alt=""
                            loading="lazy"
                        >
                    </a>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="empty">
                Belum ada foto di galeri ini.
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php if (!$isPrivate): ?>
    <script>
        window.setTimeout(function () {
            window.location.reload();
        }, 5000);
    </script>
<?php endif; ?>

</body>
</html>
