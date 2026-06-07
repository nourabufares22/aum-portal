<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Qualifications';
$userId    = getCurrentUserId();

// ── CRUD ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $degree   = trim($_POST['degree']          ?? '');
        $uni      = trim($_POST['university']      ?? '');
        $year     = (int)($_POST['graduation_year']?? 0);
        $cert     = trim($_POST['certification']   ?? '');

        if (empty($degree) || empty($uni) || $year < 1900 || $year > 2100) {
            setFlash('error', 'Degree, university and a valid graduation year are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO qualifications (user_id,degree,university,graduation_year,certification)
                                   VALUES (?,?,?,?,?)');
            $stmt->execute([$userId, $degree, $uni, $year, $cert ?: null]);
            setFlash('success', 'Qualification added successfully.');
        }

    } elseif ($action === 'edit') {
        $id     = (int)($_POST['id']              ?? 0);
        $degree = trim($_POST['degree']           ?? '');
        $uni    = trim($_POST['university']       ?? '');
        $year   = (int)($_POST['graduation_year'] ?? 0);
        $cert   = trim($_POST['certification']    ?? '');

        if (empty($degree) || empty($uni) || $year < 1900 || $year > 2100) {
            setFlash('error', 'Degree, university and a valid graduation year are required.');
        } else {
            $stmt = $pdo->prepare('UPDATE qualifications
                                   SET degree=?,university=?,graduation_year=?,certification=?
                                   WHERE id=? AND user_id=?');
            $stmt->execute([$degree, $uni, $year, $cert ?: null, $id, $userId]);
            setFlash('success', 'Qualification updated successfully.');
        }

    } elseif ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM qualifications WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Qualification deleted.');
    }

    header('Location: qualifications.php');
    exit;
}

// ── Fetch ─────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM qualifications WHERE user_id=? ORDER BY graduation_year DESC');
$stmt->execute([$userId]);
$qualifications = $stmt->fetchAll();
$currentYear    = (int)date('Y');
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

            <!-- Page header -->
            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Qualifications</h5>
                    <p class="text-muted mb-0">Manage your academic degrees and certifications.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Qualification
                </button>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($qualifications)): ?>
                    <div class="empty-state">
                        <i class="bi bi-award display-4"></i>
                        <h6 class="mt-3">No qualifications yet</h6>
                        <p class="text-muted">Add your academic degrees and certifications.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i>Add First Qualification
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Degree</th>
                                    <th>University / Institution</th>
                                    <th>Year</th>
                                    <th>Certification</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($qualifications as $i => $q): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td><strong><?= e($q['degree']) ?></strong></td>
                                    <td><?= e($q['university']) ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= e($q['graduation_year']) ?></span></td>
                                    <td><?= $q['certification'] ? e($q['certification']) : '<span class="text-muted">—</span>' ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1"
                                                onclick="openEdit(<?= htmlspecialchars(json_encode($q), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?= $q['id'] ?>,'Qualification','<?= e(addslashes($q['degree'])) ?>')">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-award me-2"></i>Add Qualification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Degree *</label>
                        <input type="text" class="form-control" name="degree"
                               placeholder="e.g. PhD in Computer Science" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">University / Institution *</label>
                        <input type="text" class="form-control" name="university"
                               placeholder="e.g. University of London" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Graduation Year *</label>
                        <input type="number" class="form-control" name="graduation_year"
                               min="1950" max="<?= $currentYear ?>" placeholder="<?= $currentYear ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Certification <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" name="certification"
                               placeholder="e.g. PMP, CCNA, etc.">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id"     id="editId">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Qualification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Degree *</label>
                        <input type="text" class="form-control" name="degree" id="editDegree" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">University / Institution *</label>
                        <input type="text" class="form-control" name="university" id="editUniversity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Graduation Year *</label>
                        <input type="number" class="form-control" name="graduation_year" id="editYear"
                               min="1950" max="<?= $currentYear ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Certification</label>
                        <input type="text" class="form-control" name="certification" id="editCertification">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete form (hidden) -->
<form id="deleteForm" method="POST" class="d-none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id"     id="deleteId">
</form>

<?php include '../includes/scripts.php'; ?>
<script>
function openEdit(q) {
    document.getElementById('editId').value          = q.id;
    document.getElementById('editDegree').value      = q.degree;
    document.getElementById('editUniversity').value  = q.university;
    document.getElementById('editYear').value        = q.graduation_year;
    document.getElementById('editCertification').value = q.certification || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function confirmDelete(id, type, name) {
    if (confirm('Delete ' + type + ': "' + name + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>
