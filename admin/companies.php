<?php
/**
 * admin/companies.php — CRUD for Record Companies/Labels
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

if ($action === 'delete' && $editId) {
    try {
        $pdo->prepare("DELETE FROM record_companies WHERE company_id=?")->execute([$editId]);
        $flash = '<div class="alert alert-success alert-dismissible fade show">Label deleted.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
    } catch(PDOException $e) {
        $flash = '<div class="alert alert-danger">Cannot delete: this label has songs linked to it.</div>';
    }
    $action = 'list';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name    = trim($_POST['name']    ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $website = trim($_POST['website'] ?? '');
    $cid     = (int)($_POST['company_id'] ?? 0);
    if (!$name) {
        $flash = '<div class="alert alert-danger">Name is required.</div>';
    } else {
        if ($cid) {
            $pdo->prepare("UPDATE record_companies SET name=?,country=?,website=? WHERE company_id=?")->execute([$name,$country,$website,$cid]);
        } else {
            $pdo->prepare("INSERT INTO record_companies(name,country,website) VALUES(?,?,?)")->execute([$name,$country,$website]);
        }
        $flash  = '<div class="alert alert-success alert-dismissible fade show">Label saved.<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>';
        $action = 'list';
    }
}

$editRow = null;
if (($action === 'edit') && $editId) {
    $s=$pdo->prepare("SELECT * FROM record_companies WHERE company_id=?"); $s->execute([$editId]); $editRow=$s->fetch();
}
$list = $pdo->query("SELECT rc.*, COUNT(s.song_id) AS song_count FROM record_companies rc LEFT JOIN songs s ON s.company_id=rc.company_id GROUP BY rc.company_id ORDER BY rc.name")->fetchAll();

$pageTitle = 'Manage Record Labels';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container-fluid"><div class="row">
<?php include 'partials/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="fw-bold mb-0">Manage Record Labels</h1>
        <a href="companies.php?action=add" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Label</a>
    </div>
    <?= $flash ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3"><?= $action==='edit'?'Edit':'Add New' ?> Record Label</h5>
        <form action="companies.php" method="POST">
            <input type="hidden" name="company_id" value="<?= $editRow['company_id'] ?? 0 ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Label Name *</label>
                    <input type="text" class="form-control" name="name" required value="<?= h($editRow['name']??'') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" name="country" value="<?= h($editRow['country']??'India') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="url" class="form-control" name="website" placeholder="https://…" value="<?= h($editRow['website']??'') ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="save" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                <a href="companies.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>#</th><th>Label Name</th><th>Country</th><th>Songs</th><th>Website</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($list as $r): ?>
                <tr>
                    <td><?= $r['company_id'] ?></td>
                    <td class="fw-semibold" style="color:#fcd34d"><?= h($r['name']) ?></td>
                    <td class="text-muted"><?= h($r['country']) ?></td>
                    <td><span class="badge bg-warning text-dark"><?= $r['song_count'] ?></span></td>
                    <td class="small">
                        <?php if($r['website']): ?>
                        <a href="<?= h($r['website']) ?>" target="_blank" class="text-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Visit
                        </a>
                        <?php else: echo '—'; endif; ?>
                    </td>
                    <td>
                        <a href="companies.php?action=edit&id=<?= $r['company_id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                        <form action="companies.php" method="GET" class="d-inline delete-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['company_id'] ?>">
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
