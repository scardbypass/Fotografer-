<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/lib/bootstrap.php';
require dirname(__DIR__) . '/lib/auth.php';
require dirname(__DIR__) . '/lib/events.php';

admin_require_login($config);

function admin_redirect(string $message, string $type = 'success'): never
{
    $_SESSION['admin_notice'] = ['message' => $message, 'type' => $type];
    header('Location: events.php', true, 303);
    exit;
}

function clean_event_id(string $value): string
{
    return (string) preg_replace('/[^a-zA-Z0-9_-]/', '', $value);
}

function event_slug(string $name): string
{
    $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', trim($name)));
    return trim($slug, '-');
}

function delete_event_directory(string $directory): void
{
    if (!is_dir($directory)) return;
    foreach (new DirectoryIterator($directory) as $item) {
        if ($item->isDot()) continue;
        if ($item->isFile()) @unlink($item->getPathname());
    }
    @rmdir($directory);
}

$events = load_events($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($config, $_POST['csrf_token'] ?? null);

    $action = (string) ($_POST['action'] ?? '');
    $eventId = clean_event_id((string) ($_POST['id'] ?? ''));

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = event_slug($name);
        if ($name === '' || $slug === '') admin_redirect('Nama event tidak valid.', 'error');

        $base = $slug;
        for ($suffix = 2; isset($events[$slug]); $suffix++) $slug = $base . '-' . $suffix;

        $visibility = ($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';
        $pin = trim((string) ($_POST['gallery_pin'] ?? ''));
        if ($visibility === 'private' && $pin !== '' && !preg_match('/^\d{4,10}$/', $pin)) {
            admin_redirect('PIN galeri harus 4–10 digit.', 'error');
        }

        $token = new_upload_token();
        $events[$slug] = [
            'id' => $slug,
            'name' => $name,
            'visibility' => $visibility,
            'active' => true,
            'token_hash' => token_hash($token),
            'gallery_pin_hash' => $pin !== '' ? password_hash($pin, PASSWORD_DEFAULT) : null,
            'created_at' => date(DATE_ATOM),
            'last_upload' => null,
            'upload_count' => 0,
        ];
        save_events($config, $events);
        $_SESSION['new_token'] = $token;
        $_SESSION['new_token_event'] = $slug;
        admin_redirect('Event berhasil dibuat.');
    }

    if ($eventId === '' || !isset($events[$eventId])) {
        admin_redirect('Event tidak ditemukan.', 'error');
    }

    if ($action === 'edit') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') admin_redirect('Nama event tidak boleh kosong.', 'error');

        $visibility = ($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';
        $pin = trim((string) ($_POST['gallery_pin'] ?? ''));

        if ($pin !== '' && !preg_match('/^\d{4,10}$/', $pin)) {
            admin_redirect('PIN galeri harus 4–10 digit.', 'error');
        }

        $events[$eventId]['name'] = $name;
        $events[$eventId]['visibility'] = $visibility;

        if (isset($_POST['remove_pin'])) {
            $events[$eventId]['gallery_pin_hash'] = null;
        } elseif ($pin !== '') {
            $events[$eventId]['gallery_pin_hash'] = password_hash($pin, PASSWORD_DEFAULT);
        }

        save_events($config, $events);
        admin_redirect('Pengaturan event disimpan.');
    }

    if ($action === 'toggle_active') {
        $events[$eventId]['active'] = !(bool) ($events[$eventId]['active'] ?? true);
        save_events($config, $events);
        admin_redirect((bool) $events[$eventId]['active'] ? 'Upload diaktifkan.' : 'Upload dinonaktifkan.');
    }

    if ($action === 'regenerate') {
        $token = new_upload_token();
        $events[$eventId]['token_hash'] = token_hash($token);
        save_events($config, $events);
        $_SESSION['new_token'] = $token;
        $_SESSION['new_token_event'] = $eventId;
        admin_redirect('Token baru dibuat. Token lama sudah tidak berlaku.');
    }

    if ($action === 'upload') {
        $files = $_FILES['media'] ?? null;
        if (!is_array($files) || !isset($files['tmp_name'])) admin_redirect('Tidak ada file yang dipilih.', 'error');

        $directory = storage_path($config) . '/' . $eventId;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            admin_redirect('Folder event gagal dibuat.', 'error');
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
        ];
        $maxBytes = max(1, (int) ($config['upload']['max_size_mb'] ?? 100)) * 1024 * 1024;
        $uploaded = 0;
        $rejected = 0;
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ((array) $files['tmp_name'] as $index => $tmp) {
            $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            $size = (int) ($files['size'][$index] ?? 0);
            if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp) || $size < 1 || $size > $maxBytes) {
                $rejected++;
                continue;
            }

            $mime = (string) $finfo->file($tmp);
            if (!isset($allowed[$mime])) {
                $rejected++;
                continue;
            }

            $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
            if (move_uploaded_file($tmp, $directory . '/' . $filename)) $uploaded++;
            else $rejected++;
        }

        if ($uploaded > 0) {
            $events[$eventId]['upload_count'] = (int) ($events[$eventId]['upload_count'] ?? 0) + $uploaded;
            $events[$eventId]['last_upload'] = date(DATE_ATOM);
            save_events($config, $events);
        }

        $message = $uploaded . ' file berhasil diupload.';
        if ($rejected > 0) $message .= ' ' . $rejected . ' file ditolak.';
        admin_redirect($message, $uploaded > 0 ? 'success' : 'error');
    }

    if ($action === 'delete') {
        delete_event_directory(storage_path($config) . '/' . $eventId);
        unset($events[$eventId]);
        save_events($config, $events);
        admin_redirect('Event dan media di dalamnya sudah dihapus.');
    }

    admin_redirect('Aksi tidak dikenali.', 'error');
}

$events = load_events($config);
$notice = $_SESSION['admin_notice'] ?? null;
$newToken = $_SESSION['new_token'] ?? null;
$newTokenEvent = $_SESSION['new_token_event'] ?? null;
unset($_SESSION['admin_notice'], $_SESSION['new_token'], $_SESSION['new_token_event']);

$csrf = htmlspecialchars(csrf_token($config), ENT_QUOTES, 'UTF-8');
$totalStats = total_storage_stats($config, $events);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#f8fafd">
    <title>Dashboard — FOTOGRAFER</title>
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body>
<header class="admin-topbar">
    <div class="admin-brand">
        <span class="admin-logo">F</span>
        <div><strong>FOTOGRAFER</strong><small>Admin Studio</small></div>
    </div>
    <div class="admin-top-actions">
        <span class="admin-event-count"><?= count($events) ?></span>
        <a class="icon-link" href="logout.php" aria-label="Keluar"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</header>

<main class="admin-main">
    <section class="admin-welcome">
        <div><span class="admin-kicker">DASHBOARD</span><h1>Galeri</h1><p>Kelola event dan kiriman foto/video.</p></div>
        <button type="button" class="admin-add" onclick="toggleBox('createBox')"><i class="bi bi-plus-lg"></i><span>Event</span></button>
    </section>

    <section class="admin-stats">
        <article><i class="bi bi-folder2"></i><div><strong><?= count($events) ?></strong><span>Event</span></div></article>
        <article><i class="bi bi-images"></i><div><strong><?= $totalStats['photos'] ?></strong><span>Foto</span></div></article>
        <article><i class="bi bi-camera-video"></i><div><strong><?= $totalStats['videos'] ?></strong><span>Video</span></div></article>
        <article><i class="bi bi-device-ssd"></i><div><strong><?= format_storage_size($totalStats['bytes']) ?></strong><span>Terpakai</span></div></article>
    </section>

    <?php if (is_array($notice)): ?>
        <div class="notice <?= ($notice['type'] ?? '') === 'error' ? 'notice-error' : '' ?>"><?= htmlspecialchars((string) ($notice['message'] ?? '')) ?></div>
    <?php endif; ?>

    <?php if ($newToken): ?>
        <section class="admin-token">
            <div class="admin-token-head"><div><span>UPLOAD TOKEN</span><strong><?= htmlspecialchars((string) $newTokenEvent) ?></strong></div><i class="bi bi-key"></i></div>
            <code id="newToken"><?= htmlspecialchars((string) $newToken) ?></code>
            <button type="button" onclick="copyText('newToken',this)"><i class="bi bi-copy"></i> Salin token</button>
            <small>Token hanya ditampilkan setelah dibuat atau diperbarui.</small>
        </section>
    <?php endif; ?>

    <section id="createBox" class="admin-create">
        <div class="admin-section-head">
            <div><span>EVENT BARU</span><h2>Buat galeri</h2></div>
            <button type="button" class="admin-close" onclick="toggleBox('createBox',false)"><i class="bi bi-x-lg"></i></button>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create">
            <label><span>Nama event</span><input name="name" maxlength="100" required placeholder="Wedding Andi & Sinta"></label>
            <div class="admin-form-row">
                <label><span>Akses</span><select name="visibility"><option value="public">Public</option><option value="private">Private + PIN</option></select></label>
                <label><span>PIN galeri <em>4–10 digit</em></span><input name="gallery_pin" inputmode="numeric" pattern="[0-9]{4,10}" maxlength="10" placeholder="123456"></label>
            </div>
            <button type="submit" class="admin-submit"><i class="bi bi-plus-circle"></i> Buat event</button>
        </form>
    </section>

    <div class="admin-list-title"><span>EVENT</span><small><?= count($events) ?> total</small></div>
    <section class="admin-events">
    <?php foreach (array_reverse($events, true) as $event):
        $rawId = (string) ($event['id'] ?? '');
        $htmlId = htmlspecialchars($rawId, ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars((string) ($event['name'] ?? $rawId), ENT_QUOTES, 'UTF-8');
        $private = ($event['visibility'] ?? 'private') === 'private';
        $active = (bool) ($event['active'] ?? true);
        $hasPin = !empty($event['gallery_pin_hash']);
        $stats = event_storage_stats($config, $rawId);
    ?>
        <article class="admin-event-card">
            <div class="event-head">
                <div>
                    <span class="event-status-dot <?= $active ? 'on' : 'off' ?>"></span><h2><?= $name ?></h2>
                    <p class="meta">/<?= $htmlId ?> · <?= $active ? 'Upload aktif' : 'Upload nonaktif' ?></p>
                    <div class="event-stats">
                        <span><i class="bi bi-image"></i><b><?= $stats['photos'] ?></b> Foto</span>
                        <span><i class="bi bi-camera-video"></i><b><?= $stats['videos'] ?></b> Video</span>
                        <span><i class="bi bi-device-ssd"></i><b><?= format_storage_size($stats['bytes']) ?></b></span>
                    </div>
                </div>
                <span class="badge <?= $private ? 'private' : '' ?>"><?= $private ? ($hasPin ? 'PRIVATE + PIN' : 'PRIVATE') : 'PUBLIC' ?></span>
            </div>

            <div class="event-actions">
                <button type="button" class="event-action primary-action" onclick="document.getElementById('up-<?= $htmlId ?>').click()"><i class="bi bi-cloud-arrow-up"></i><span>Upload</span></button>
                <a class="event-action" href="../gallery.php?event=<?= urlencode($rawId) ?>" target="_blank" rel="noopener"><i class="bi bi-grid-3x3-gap"></i><span>Galeri</span></a>
                <button type="button" class="event-action" onclick="toggleBox('set-<?= $htmlId ?>')"><i class="bi bi-sliders"></i><span>Atur</span></button>
            </div>

            <form method="post" enctype="multipart/form-data" class="hidden-upload">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="id" value="<?= $htmlId ?>">
                <input id="up-<?= $htmlId ?>" type="file" name="media[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" multiple onchange="submitUpload(this)">
            </form>

            <div id="set-<?= $htmlId ?>" class="event-settings">
                <form method="post" class="form-grid compact">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $htmlId ?>">
                    <label>Nama<input name="name" maxlength="100" required value="<?= $name ?>"></label>
                    <label>Akses<select name="visibility"><option value="public" <?= !$private ? 'selected' : '' ?>>Public</option><option value="private" <?= $private ? 'selected' : '' ?>>Private</option></select></label>
                    <label>PIN private<input name="gallery_pin" inputmode="numeric" pattern="[0-9]{4,10}" maxlength="10" placeholder="<?= $hasPin ? 'PIN sudah aktif' : 'Buat PIN baru' ?>"></label>
                    <button type="submit">Simpan</button>
                    <?php if ($hasPin): ?><label class="remove-pin"><input type="checkbox" name="remove_pin" value="1"> Hapus PIN saat disimpan</label><?php endif; ?>
                </form>

                <div class="danger-actions">
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="regenerate"><input type="hidden" name="id" value="<?= $htmlId ?>"><button class="secondary" type="submit">Buat token baru</button></form>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="<?= $htmlId ?>"><button class="secondary" type="submit"><?= $active ? 'Matikan upload' : 'Aktifkan upload' ?></button></form>
                    <form method="post" onsubmit="return confirm('Hapus event <?= addslashes($name) ?> beserta semua foto/video? Tindakan ini tidak dapat dibatalkan.')"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $htmlId ?>"><button class="danger" type="submit">Hapus event</button></form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$events): ?><div class="home-empty"><strong>Belum ada event.</strong><p>Buat event pertama untuk mulai menerima media.</p></div><?php endif; ?>
    </section>
</main>

<script>
function toggleBox(id, force) {
    const el = document.getElementById(id);
    if (!el) return;
    if (force === false) el.classList.remove('open');
    else el.classList.toggle('open');
}
function copyText(id, button) {
    const text = document.getElementById(id)?.textContent || '';
    navigator.clipboard.writeText(text).then(() => {
        const original = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
        setTimeout(() => button.innerHTML = original, 1400);
    });
}
function submitUpload(input) {
    if (!input.files || !input.files.length) return;
    input.closest('form').submit();
}
</script>
</body>
</html>
