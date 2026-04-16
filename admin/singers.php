<?php
/**
 * admin/singers.php — CRUD for Singers
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

requireAdmin();
$pdo    = getPDO();
$flash  = '';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// DELETE
if ($action === 'delete' && $editId) {
    $pdo->prepare("DELETE FROM singers WHERE singer_id=?")->execute([$editId]);
    $flash = '<div class="alert alert-success alert-dismissible fade show">Singer deleted.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
    $action = 'list';
}

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = trim($_POST['name'] ?? '');
    $nat  = trim($_POST['nationality'] ?? 'Indian');
    $bio  = trim($_POST['bio'] ?? '');
    $sid  = (int)($_POST['singer_id'] ?? 0);
    if (!$name) {
        $flash = '<div class="alert alert-danger">Name is required.</div>';
    } else {
        if ($sid) {
            $pdo->prepare("UPDATE singers SET name=?,nationality=?,bio=? WHERE singer_id=?")->execute([$name,$nat,$bio,$sid]);
        } else {
            $pdo->prepare("INSERT INTO singers(name,nationality,bio) VALUES(?,?,?)")->execute([$name,$nat,$bio]);
        }
        $flash  = '<div class="alert alert-success alert-dismissible fade show">Singer saved.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
        $action = 'list';
    }
}

$editRow = null;
if (($action === 'edit') && $editId) {
    $s=$pdo->prepare("SELECT * FROM singers WHERE singer_id=?"); $s->execute([$editId]); $editRow=$s->fetch();
}
$list = $pdo->query("SELECT s.*, COUNT(ss.song_id) AS song_count FROM singers s LEFT JOIN song_singers ss ON ss.singer_id=s.singer_id GROUP BY s.singer_id ORDER BY s.name")->fetchAll();

$pageTitle = 'Manage Singers';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container-fluid"><div class="row">
<?php include 'partials/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="fw-bold mb-0">Manage Singers</h1>
        <a href="singers.php?action=add" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Singer</a>
    </div>
    <?= $flash ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3"><?= $action==='edit'?'Edit':'Add New' ?> Singer</h5>
        <form action="singers.php" method="POST">
            <input type="hidden" name="singer_id" value="<?= $editRow['singer_id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Name *</label>
                    <input type="text" class="form-control" name="name" required value="<?= h($editRow['name']??'') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nationality</label>
                    <input type="text" class="form-control" name="nationality" value="<?= h($editRow['nationality']??'Indian') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Bio</label>
                    <textarea class="form-control" name="bio" rows="2"><?= h($editRow['bio']??'') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="save" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                <a href="singers.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>#</th><th>Name</th><th>Nationality</th><th>Songs</th><th>Bio</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($list as $r): ?>
                <tr>
                    <td><?= $r['singer_id'] ?></td>
                    <td class="fw-semibold" style="color:#a78bfa"><?= h($r['name']) ?></td>
                    <td class="text-muted"><?= h($r['nationality']) ?></td>
                    <td><span class="badge bg-primary"><?= $r['song_count'] ?></span></td>
                    <td class="small text-muted"><?= h(substr($r['bio']??'',0,50)) ?>…</td>
                    <td>
                        <a href="singers.php?action=edit&id=<?= $r['singer_id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <form action="singers.php" method="GET" class="d-inline delete-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['singer_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div></div>
</main>
<?php include ROOT_PATH . 'includes/footer.php'; ?>
