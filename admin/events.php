<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/lib/bootstrap.php';

require dirname(__DIR__) . '/lib/auth.php';
require dirname(__DIR__) . '/lib/events.php';

admin_require_login($config);

$events = load_events($config);
$generatedToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require($config, $_POST['csrf_token'] ?? null);

    $action = (string) ($_POST['action'] ?? '');
    $eventId = (string) ($_POST['id'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $eventId = trim(
            strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name)),
            '-'
        );

        if ($name !== '' && $eventId !== '') {
            $baseId = $eventId;
            $suffix = 2;

            while (isset($events[$eventId])) {
                $eventId = $baseId . '-' . $suffix++;
            }

            $generatedToken = new_upload_token();

            $events[$eventId] = [
                'id' => $eventId,
                'name' => $name,
                'visibility' => ($_POST['visibility'] ?? 'private') === 'public'
                    ? 'public'
                    : 'private',
                'active' => true,
                'token_hash' => token_hash($generatedToken),
                'created_at' => date(DATE_ATOM),
                'last_upload' => null,
                'upload_count' => 0,
            ];

            save_events($config, $events);
        }
    }

    if ($action === 'regenerate' && isset($events[$eventId])) {
        $generatedToken = new_upload_token();
        $events[$eventId]['token_hash'] = token_hash($generatedToken);
        save_events($config, $events);
    }

    if ($action === 'visibility' && isset($events[$eventId])) {
        $current = $events[$eventId]['visibility'] ?? 'private';
        $events[$eventId]['visibility'] = $current === 'public'
            ? 'private'
            : 'public';

        save_events($config, $events);
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event — FOTOGRAFER</title>
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body>
<header class="sitebar">
    <div class="brand">
        FOTOGRAFER
        <small>OPERATOR CONSOLE</small>
    </div>
    <nav class="operator-nav">
        <span class="count"><?= count($events) ?> EVENT</span>
        <a href="logout.php">Keluar</a>
    </nav>
</header>

<main class="wrap">
    <div class="topline">
        <div>
            <div class="eyebrow">Workspace</div>
            <h1>Event & Folder</h1>
            <p>Buat tujuan upload, atur privasi galeri, dan kelola token tablet.</p>
        </div>
    </div>

    <?php if ($generatedToken): ?>
        <section class="token">
            <div class="eyebrow">Token baru — copy sekarang</div>
            <code><?= htmlspecialchars($generatedToken) ?></code>
            <p>Token asli tidak disimpan. Buat token baru bila token hilang atau bocor.</p>
        </section>
    <?php endif; ?>

    <form class="create panel" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token($config), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create">
        <input name="name" required placeholder="Nama event, mis. Wedding Andi">
        <select name="visibility">
            <option value="private">Private</option>
            <option value="public">Public</option>
        </select>
        <button type="submit">Buat event</button>
    </form>

    <div class="events">
        <?php foreach (array_reverse($events, true) as $event): ?>
            <?php $isPrivate = ($event['visibility'] ?? 'private') === 'private'; ?>
            <article class="event">
                <div class="event-head">
                    <div>
                        <h2><?= htmlspecialchars($event['name']) ?></h2>
                        <p class="meta">
                            /<?= htmlspecialchars($event['id']) ?>
                            · <?= (int) ($event['upload_count'] ?? 0) ?> foto
                        </p>
                    </div>
                    <span class="badge <?= $isPrivate ? 'private' : '' ?>">
                        <?= $isPrivate ? 'PRIVATE' : 'PUBLIC' ?>
                    </span>
                </div>

                <p class="meta">
                    Upload terakhir:
                    <?= htmlspecialchars($event['last_upload'] ?? 'Belum ada') ?>
                </p>

                <div class="actions">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token($config), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="visibility">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($event['id']) ?>">
                        <button class="secondary" type="submit">Ubah akses</button>
                    </form>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token($config), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="regenerate">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($event['id']) ?>">
                        <button type="submit">Token baru</button>
                    </form>

                    <?php if (!$isPrivate): ?>
                        <a href="../?event=<?= urlencode($event['id']) ?>" target="_blank" rel="noopener noreferrer">
                            Buka galeri
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
