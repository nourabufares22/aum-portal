<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Publications';
$userId    = getCurrentUserId();

// -- CRUD ------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title   = trim($_POST['title']            ?? '');
        $journal = trim($_POST['journal']          ?? '');
        $year    = (int)($_POST['publication_year']?? 0);
        $ri      = trim($_POST['research_interest']?? '');

        if (empty($title) || empty($journal) || $year < 1900 || $year > 2100) {
            setFlash('error', 'Title, journal and a valid year are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO publications (user_id,title,journal,publication_year,research_interest)
                                   VALUES (?,?,?,?,?)');
            $stmt->execute([$userId, $title, $journal, $year, $ri ?: null]);
            setFlash('success', 'Publication added successfully.');
        }

    } elseif ($action === 'edit') {
        $id      = (int)($_POST['id']             ?? 0);
        $title   = trim($_POST['title']           ?? '');
        $journal = trim($_POST['journal']         ?? '');
        $year    = (int)($_POST['publication_year']?? 0);
        $ri      = trim($_POST['research_interest']?? '');

        if (empty($title) || empty($journal) || $year < 1900 || $year > 2100) {
            setFlash('error', 'Title, journal and a valid year are required.');
        } else {
            $stmt = $pdo->prepare('UPDATE publications
                                   SET title=?,journal=?,publication_year=?,research_interest=?
                                   WHERE id=? AND user_id=?');
            $stmt->execute([$title, $journal, $year, $ri ?: null, $id, $userId]);
            setFlash('success', 'Publication updated.');
        }

    } elseif ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM publications WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Publication deleted.');
    }

    header('Location: publications.php');
    exit;
}

// -- Fetch -----------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM publications WHERE user_id=? ORDER BY publication_year DESC');
$stmt->execute([$userId]);
$publications = $stmt->fetchAll();
$currentYear  = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/head.php'; ?>
<body>
<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include '../includes/navbar.php'; ?>
        <div class="content-area">
            <?php displayFlash(); ?>

            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Publications</h5>
                    <p class="text-muted mb-0">Manage your research publications and journal articles.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Publication
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($publications)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-richtext display-4"></i>
                        <h6 class="mt-3">No publications yet</h6>
                        <p class="text-muted">Add your research publications and journal articles.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i>Add First Publication
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Journal</th>
                                    <th>Year</th>
                                    <th>Research Interest</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($publications as $i => $p): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <strong class="d-block"><?= e($p['title']) ?></strong>
                                    </td>
                                    <td><?= e($p['journal']) ?></td>
                                    <td><span class="badge bg-success-subtle text-success"><?= e($p['publication_year']) ?></span></td>
                                    <td><?= $p['research_interest'] ? e($p['research_interest']) : '<span class="text-muted">-</span>' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1"
                                                onclick="openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?= $p['id'] ?>,'Publication','<?= e(addslashes($p['title'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-journal-plus me-2"></i>Add Publication</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Publication Title *</label>
                        <input type="text" class="form-control" name="title"
                               placeholder="Full title of the paper or article" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Journal / Conference *</label>
                            <input type="text" class="form-control" name="journal"
                                   placeholder="e.g. IEEE Transactions on..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Publication Year *</label>
                            <input type="number" class="form-control" name="publication_year"
                                   min="1950" max="<?= $currentYear ?>" placeholder="<?= $currentYear ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Research Interest <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" name="research_interest"
                               placeholder="e.g. Machine Learning, NLP, Cryptography">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-plus-lg me-1"></i>Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Publication</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Publication Title *</label>
                        <input type="text" class="form-control" name="title" id="editTitle" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Journal / Conference *</label>
                            <input type="text" class="form-control" name="journal" id="editJournal" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Publication Year *</label>
                            <input type="number" class="form-control" name="publication_year" id="editYear"
                                   min="1950" max="<?= $currentYear ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Research Interest</label>
                        <input type="text" class="form-control" name="research_interest" id="editRI">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" class="d-none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php include '../includes/scripts.php'; ?>
<script>
function openEdit(p) {
    document.getElementById('editId').value     = p.id;
    document.getElementById('editTitle').value  = p.title;
    document.getElementById('editJournal').value= p.journal;
    document.getElementById('editYear').value   = p.publication_year;
    document.getElementById('editRI').value     = p.research_interest || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
function confirmDelete(id, type, name) {
    if (confirm('Delete ' + type + ': "' + name + '"?\n\nThis cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>
