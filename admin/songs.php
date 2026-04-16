<?php
/**
 * admin/songs.php — CRUD for Songs
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

requireAdmin();

$pdo   = getPDO();
$flash = '';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── DELETE ──────────────────────────────────────────────
if ($action === 'delete' && $editId) {
    $pdo->prepare("DELETE FROM songs WHERE song_id = ?")->execute([$editId]);
    $flash = '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>Song deleted.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
    $action = 'list';
}

// ── SAVE (add/edit) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_song'])) {
    $d = [
        'title'        => trim($_POST['title']         ?? ''),
        'movie_name'   => trim($_POST['movie_name']    ?? ''),
        'price'        => (float)($_POST['price']      ?? 0),
        'duration'     => trim($_POST['duration']      ?? '00:03:00'),
        'category'     => trim($_POST['category']      ?? 'Other'),
        'available_as' => trim($_POST['available_as']  ?? 'MP3'),
        'size_mb'      => (float)($_POST['size_mb']    ?? 0),
        'company_id'   => (int)($_POST['company_id']   ?? 0),
    ];
    $singerIds   = array_map('intval', (array)($_POST['singer_ids']   ?? []));
    $composerIds = array_map('intval', (array)($_POST['composer_ids'] ?? []));

    if (!$d['title'] || !$d['movie_name'] || !$d['company_id']) {
        $flash = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Please fill all required fields.</div>';
    } else {
        $sid = (int)($_POST['song_id'] ?? 0);
        if ($sid) {
            $pdo->prepare("UPDATE songs SET title=?,movie_name=?,price=?,duration=?,category=?,
                           available_as=?,size_mb=?,company_id=? WHERE song_id=?")
                ->execute([$d['title'],$d['movie_name'],$d['price'],$d['duration'],$d['category'],
                           $d['available_as'],$d['size_mb'],$d['company_id'],$sid]);
        } else {
            $pdo->prepare("INSERT INTO songs(title,movie_name,price,duration,category,available_as,size_mb,company_id)
                           VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$d['title'],$d['movie_name'],$d['price'],$d['duration'],$d['category'],
                           $d['available_as'],$d['size_mb'],$d['company_id']]);
            $sid = (int)$pdo->lastInsertId();
        }
        // Sync junction tables
        $pdo->prepare("DELETE FROM song_singers   WHERE song_id=?")->execute([$sid]);
        $pdo->prepare("DELETE FROM song_composers WHERE song_id=?")->execute([$sid]);
        foreach ($singerIds as $si)   if($si) $pdo->prepare("INSERT IGNORE INTO song_singers(song_id,singer_id) VALUES(?,?)")->execute([$sid,$si]);
        foreach ($composerIds as $ci) if($ci) $pdo->prepare("INSERT IGNORE INTO song_composers(song_id,composer_id) VALUES(?,?)")->execute([$sid,$ci]);

        $flash = '<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>Song saved successfully.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
        $action = 'list';
    }
}

// ── LOAD AUXILIARY LISTS ─────────────────────────────────
$singers   = $pdo->query("SELECT * FROM singers   ORDER BY name")->fetchAll();
$composers = $pdo->query("SELECT * FROM composers ORDER BY name")->fetchAll();
$companies = $pdo->query("SELECT * FROM record_companies ORDER BY name")->fetchAll();
$categories= ['Romantic','Sad','Party','Sufi','Folk','Patriotic','Classical','Devotional','Item','Other'];
$formats   = ['MP3','WAV','Both'];

// ── LOAD SONG FOR EDITING ────────────────────────────────
$editSong    = null;
$editSingers = [];
$editComposers = [];
if (($action === 'edit') && $editId) {
    $editSong = $pdo->prepare("SELECT * FROM songs WHERE song_id=?");
    $editSong->execute([$editId]);
    $editSong = $editSong->fetch();
    if ($editSong) {
        $editSingers = array_column(
            $pdo->prepare("SELECT singer_id FROM song_singers WHERE song_id=?")->execute([$editId]) ? [] : [],
            'singer_id'
        );
        $r=$pdo->prepare("SELECT singer_id FROM song_singers WHERE song_id=?"); $r->execute([$editId]);
        $editSingers = array_column($r->fetchAll(), 'singer_id');
        $r=$pdo->prepare("SELECT composer_id FROM song_composers WHERE song_id=?"); $r->execute([$editId]);
        $editComposers = array_column($r->fetchAll(), 'composer_id');
    }
}

// ── SONG LIST ───────────────────────────────────────────
$songs = $pdo->query("SELECT * FROM v_song_details ORDER BY song_id DESC")->fetchAll();

$pageTitle = 'Manage Songs';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container-fluid">
<div class="row">
<?php include 'partials/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="fw-bold mb-0">Manage Songs</h1>
        <a href="songs.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Song
        </a>
    </div>
    <?= $flash ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- ======================== FORM ======================== -->
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3"><?= $action === 'edit' ? 'Edit' : 'Add New' ?> Song</h5>
        <form action="songs.php" method="POST" id="songForm">
            <input type="hidden" name="song_id" value="<?= $editSong['song_id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Song Title *</label>
                    <input type="text" class="form-control" name="title" required
                           value="<?= h($editSong['title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Movie Name *</label>
                    <input type="text" class="form-control" name="movie_name" required
                           value="<?= h($editSong['movie_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price (₹) *</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="price" required
                           value="<?= $editSong['price'] ?? 20 ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duration (HH:MM:SS)</label>
                    <input type="text" class="form-control" name="duration" pattern="\d{2}:\d{2}:\d{2}"
                           placeholder="00:04:00"
                           value="<?= h($editSong['duration'] ?? '00:04:00') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <?php foreach($categories as $c): ?>
                        <option value="<?= $c ?>" <?= (($editSong['category'] ?? '')===  $c) ? 'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Available As</label>
                    <select class="form-select" name="available_as">
                        <?php foreach($formats as $f): ?>
                        <option value="<?= $f ?>" <?= (($editSong['available_as'] ?? '')=== $f) ? 'selected':'' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Size (MB)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="size_mb"
                           value="<?= $editSong['size_mb'] ?? 5 ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Record Label *</label>
                    <select class="form-select" name="company_id" required>
                        <option value="">— Select —</option>
                        <?php foreach($companies as $c): ?>
                        <option value="<?= $c['company_id'] ?>"
                            <?= (($editSong['company_id'] ?? 0)=== $c['company_id']) ? 'selected':'' ?>>
                            <?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Singer(s) — hold Ctrl/Cmd to select multiple</label>
                    <select class="form-select" name="singer_ids[]" multiple size="5">
                        <?php foreach($singers as $s): ?>
                        <option value="<?= $s['singer_id'] ?>"
                            <?= in_array($s['singer_id'],$editSingers) ? 'selected':'' ?>>
                            <?= h($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Composer(s) — hold Ctrl/Cmd to select multiple</label>
                    <select class="form-select" name="composer_ids[]" multiple size="5">
                        <?php foreach($composers as $c): ?>
                        <option value="<?= $c['composer_id'] ?>"
                            <?= in_array($c['composer_id'],$editComposers) ? 'selected':'' ?>>
                            <?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="save_song" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Song
                </button>
                <a href="songs.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ======================== LIST ======================== -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Title</th><th>Movie</th><th>Singer(s)</th><th>Price</th><th>Format</th><th>Category</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach($songs as $s): ?>
                <tr>
                    <td><?= $s['song_id'] ?></td>
                    <td class="fw-semibold"><?= h($s['title']) ?></td>
                    <td class="text-muted small"><?= h($s['movie_name']) ?></td>
                    <td class="small" style="color:#a78bfa"><?= h($s['singers'] ?? '—') ?></td>
                    <td class="text-accent fw-semibold">₹<?= $s['price'] ?></td>
                    <td><span class="badge bg-secondary"><?= $s['available_as'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['category']) ?>"><?= $s['category'] ?></span></td>
                    <td>
                        <a href="songs.php?action=edit&id=<?= $s['song_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="songs.php?action=delete&id=<?= $s['song_id'] ?>" method="GET"
                              class="d-inline delete-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['song_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
</main>
<?php include ROOT_PATH . 'includes/footer.php'; ?>
