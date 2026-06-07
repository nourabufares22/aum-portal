<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Experience';
$userId    = getCurrentUserId();

// ── CRUD ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $inst  = trim($_POST['institution']        ?? '');
        $pos   = trim($_POST['position']           ?? '');
        $years = (int)($_POST['years_of_experience'] ?? 0);

        if (empty($inst) || empty($pos) || $years < 0) {
            setFlash('error', 'Institution, position, and years are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO experiences (user_id,institution,position,years_of_experience)
                                   VALUES (?,?,?,?)');
            $stmt->execute([$userId, $inst, $pos, $years]);
            setFlash('success', 'Experience added successfully.');
        }

    } elseif ($action === 'edit') {
        $id    = (int)($_POST['id']                 ?? 0);
        $inst  = trim($_POST['institution']         ?? '');
        $pos   = trim($_POST['position']            ?? '');
        $years = (int)($_POST['years_of_experience']?? 0);

        if (empty($inst) || empty($pos) || $years < 0) {
            setFlash('error', 'Institution, position, and years are required.');
        } else {
            $stmt = $pdo->prepare('UPDATE experiences
                                   SET institution=?,position=?,years_of_experience=?
                                   WHERE id=? AND user_id=?');
            $stmt->execute([$inst, $pos, $years, $id, $userId]);
            setFlash('success', 'Experience updated.');
        }

    } elseif ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM experiences WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Experience deleted.');
    }

    header('Location: experience.php');
    exit;
}

// ── Fetch ─────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM experiences WHERE user_id=? ORDER BY years_of_experience DESC, created_at DESC');
$stmt->execute([$userId]);
$experiences = $stmt->fetchAll();
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
                    <h5 class="mb-1">Experience</h5>
                    <p class="text-muted mb-0">Manage your professional and academic experience.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Experience
                </button>
            </div>

            <!-- Experience cards grid -->
            <?php if (empty($experiences)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-briefcase display-4"></i>
                        <h6 class="mt-3">No experience entries yet</h6>
                        <p class="text-muted">Add your academic and professional experience.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i>Add Experience
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($experiences as $exp): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 exp-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="exp-icon-wrap">
                                    <i class="bi bi-briefcase-fill"></i>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-secondary"
                                            onclick="openEdit(<?= htmlspecialchars(json_encode($exp), ENT_QUOTES) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(<?= $exp['id'] ?>,'Experience','<?= e(addslashes($exp['position'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h6 class="mb-1"><?= e($exp['position']) ?></h6>
                            <p class="text-muted mb-2"><?= e($exp['institution']) ?></p>
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="bi bi-clock me-1"></i>
                                <?= $exp['years_of_experience'] ?> year<?= $exp['years_of_experience'] != 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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
                    <h5 class="modal-title"><i class="bi bi-briefcase me-2"></i>Add Experience</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Institution / Organization *</label>
                        <input type="text" class="form-control" name="institution"
                               placeholder="e.g. American University of Madaba" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Position / Role *</label>
                        <input type="text" class="form-control" name="position"
                               placeholder="e.g. Assistant Professor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Years of Experience *</label>
                        <input type="number" class="form-control" name="years_of_experience"
                               min="0" max="60" placeholder="e.g. 5" required>
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
                <input type="hidden" name="id" id="editId">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Experience</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Institution / Organization *</label>
                        <input type="text" class="form-control" name="institution" id="editInstitution" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Position / Role *</label>
                        <input type="text" class="form-control" name="position" id="editPosition" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Years of Experience *</label>
                        <input type="number" class="form-control" name="years_of_experience" id="editYears"
                               min="0" max="60" required>
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
function openEdit(exp) {
    document.getElementById('editId').value          = exp.id;
    document.getElementById('editInstitution').value = exp.institution;
    document.getElementById('editPosition').value    = exp.position;
    document.getElementById('editYears').value       = exp.years_of_experience;
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
