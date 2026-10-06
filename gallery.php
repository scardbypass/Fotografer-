<?php

declare(strict_types=1);

$config = require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/events.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$events = load_events($config);
$eventId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_GET['event'] ?? ''));
$event = $eventId !== '' ? ($events[$eventId] ?? null) : null;

if ($event === null) {
    http_response_code(404);
    $pageTitle = 'Galeri tidak ditemukan';
    $state = 'missing';
    $photos = [];
} elseif (($event['visibility'] ?? 'private') !== 'public' && !($_SESSION['gallery_access'][$eventId] ?? false)) {
    $pageTitle = (string) ($event['name'] ?? 'Galeri privat');
    $pinError = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pin = trim((string) ($_POST['gallery_pin'] ?? ''));
        $hash = (string) ($event['gallery_pin_hash'] ?? '');
        if ($hash !== '' && password_verify($pin, $hash)) {
            $_SESSION['gallery_access'][$eventId] = true;
            header('Location: gallery.php?event=' . urlencode($eventId)); exit;
        }
        $pinError = true;
    }
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
        <p><?= $state === 'missing' ? 'Link galeri tidak valid atau event sudah tidak tersedia.' : 'Masukkan PIN yang diberikan fotografer untuk membuka galeri ini.' ?></p>
        <?php if ($state === 'private'): ?><form method="post" class="private-pin-form"><input type="password" name="gallery_pin" inputmode="numeric" placeholder="PIN galeri" required autofocus><button type="submit">Buka galeri</button></form><?php if (!empty($pinError)): ?><p class="pin-error">PIN salah. Coba lagi.</p><?php endif; ?><?php else: ?><a class="button primary" href="./">Kembali ke beranda</a><?php endif; ?>
    </section>
<?php else: ?>
    <section class="gallery-heading">
        <div><span class="eyebrow">Galeri event</span><h1><?= $safeTitle ?></h1><p>Foto terbaru akan muncul otomatis selama event berlangsung.</p></div>
        <span class="badge">PUBLIC</span>
    </section>

    <?php if ($photos): ?>
        <form id="downloadForm" method="post" action="download.php">
        <input type="hidden" name="event" value="<?= htmlspecialchars($eventId, ENT_QUOTES, 'UTF-8') ?>">
        <div class="gallery-tools"><div><strong><span id="selectedCount">0</span> dipilih</strong><span><?= count($photos) ?> foto tersedia</span></div><div><button type="button" class="gallery-tool-btn" id="selectAll">Pilih semua</button><button type="button" class="gallery-tool-btn" id="clearAll">Batal</button></div></div>
        <section class="select-gallery">
        <?php foreach ($photos as $i => $photo): $url = 'photo.php?' . http_build_query(['event' => $eventId, 'file' => $photo]); $downloadUrl = $url . '&download=1'; ?>
            <article class="select-photo">
                <label class="photo-select"><input class="photo-check" type="checkbox" name="files[]" value="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>"><span class="check-ui"><i class="bi bi-check-lg"></i></span><img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="Foto <?= $safeTitle ?>" loading="lazy"></label>
                <div class="photo-actions"><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><i class="bi bi-arrows-fullscreen"></i></a><a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-download"></i></a></div>
            </article>
        <?php endforeach; ?>
        </section>
        <div class="download-bar" id="downloadBar"><div><strong id="barCount">0 foto</strong><span>Siap didownload</span></div><button type="submit" id="downloadSelected" disabled><i class="bi bi-download"></i> Download terpilih</button></div>
        </form>
    <?php else: ?>
        <section class="home-empty"><strong>Belum ada foto.</strong><p>Halaman ini akan memperbarui diri saat foto pertama masuk.</p></section>
    <?php endif; ?>
<?php endif; ?>
</main>

<?php if ($state === 'public'): ?><script>
const checks=[...document.querySelectorAll('.photo-check')],count=document.getElementById('selectedCount'),barCount=document.getElementById('barCount'),download=document.getElementById('downloadSelected'),bar=document.getElementById('downloadBar');
function sync(){const n=checks.filter(x=>x.checked).length;count.textContent=n;barCount.textContent=n+' foto';download.disabled=n===0;bar.classList.toggle('show',n>0);checks.forEach(x=>x.closest('.select-photo').classList.toggle('selected',x.checked))}
checks.forEach(x=>x.addEventListener('change',sync));
document.getElementById('selectAll').onclick=()=>{checks.forEach(x=>x.checked=true);sync()};
document.getElementById('clearAll').onclick=()=>{checks.forEach(x=>x.checked=false);sync()};sync();
</script><?php endif; ?>
</body>
</html>
