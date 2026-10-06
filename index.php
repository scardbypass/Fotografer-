<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/events.php';

$events = load_events($config);
$publicEvents = array_filter(
    $events,
    static fn (array $event): bool =>
        ($event['visibility'] ?? 'private') === 'public'
        && (bool) ($event['active'] ?? true)
);

$publicEvents = array_reverse($publicEvents, true);
$appName = htmlspecialchars((string) ($config['app']['name'] ?? 'FOTOGRAFER'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Galeri foto instan. Temukan, lihat, dan download foto kamu dengan mudah.">
    <title><?= $appName ?> — Instant Photo Delivery</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="sitebar">
    <a class="brand brand-link" href="./">
        <?= $appName ?>
        <small>INSTANT PHOTO DELIVERY</small>
    </a>
    <nav class="main-nav" aria-label="Navigasi utama">
        <a href="#galeri">Galeri</a>
        <a href="#cara-kerja">Cara kerja</a>
        <a class="nav-button" href="admin/login.php">Admin</a>
    </nav>
</header>

<main>
    <section class="home-hero">
        <div class="hero-copy">
            <span class="hero-kicker">PHOTO DELIVERY, MADE SIMPLE</span>
            <h1>Temukan fotomu.<br>Tanpa menunggu lama.</h1>
            <p>Foto dari fotografer langsung masuk ke galeri online. Pilih event kamu, lihat hasilnya, lalu simpan foto favoritmu.</p>
            <div class="hero-actions">
                <a class="button primary" href="#galeri">Lihat galeri</a>
                <a class="button ghost" href="#cara-kerja">Cara kerja</a>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="frame frame-main"><span>FOTOGRAFER</span></div>
            <div class="frame frame-small"><span>INSTANT</span></div>
            <div class="live-pill"><i></i> PHOTO READY</div>
        </div>
    </section>

    <section class="home-section" id="galeri">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Galeri publik</span>
                <h2>Temukan event kamu</h2>
            </div>
            <p>Galeri terbaru yang tersedia untuk publik.</p>
        </div>

        <?php if ($publicEvents): ?>
            <div class="public-events">
                <?php foreach ($publicEvents as $eventId => $event): ?>
                    <a class="public-event" href="gallery.php?event=<?= urlencode((string) $eventId) ?>">
                        <div class="event-cover">
                            <span class="event-index"><?= str_pad((string) ((int) array_search($eventId, array_keys($publicEvents), true) + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="event-arrow">↗</span>
                        </div>
                        <div class="public-event-body">
                            <div>
                                <h3><?= htmlspecialchars((string) ($event['name'] ?? $eventId), ENT_QUOTES, 'UTF-8') ?></h3>
                                <span><?= (int) ($event['upload_count'] ?? 0) ?> foto</span>
                            </div>
                            <span class="badge">PUBLIC</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="home-empty">
                <strong>Belum ada galeri publik.</strong>
                <p>Galeri akan muncul di sini setelah fotografer mempublikasikan event.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="process-section" id="cara-kerja">
        <div class="section-heading light">
            <div>
                <span class="eyebrow">Cara kerja</span>
                <h2>Dari kamera ke kamu.</h2>
            </div>
            <p>Alur sederhana supaya foto bisa diterima lebih cepat.</p>
        </div>
        <div class="steps">
            <article><span>01</span><h3>Difoto</h3><p>Fotografer mengambil foto saat event berlangsung.</p></article>
            <article><span>02</span><h3>Terkirim</h3><p>Foto dikirim ke sistem dan masuk ke galeri event.</p></article>
            <article><span>03</span><h3>Pilih</h3><p>Buka galeri dan temukan hasil foto yang kamu suka.</p></article>
            <article><span>04</span><h3>Simpan</h3><p>Buka foto ukuran penuh dan simpan ke perangkatmu.</p></article>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="brand"><?= $appName ?><small>INSTANT PHOTO DELIVERY</small></div>
    <p>&copy; <?= date('Y') ?> <?= $appName ?>. All rights reserved.</p>
</footer>
</body>
</html>
