<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Skills';
$userId    = getCurrentUserId();

// ── CRUD ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $skill = trim($_POST['skill_name'] ?? '');
        if (empty($skill)) {
            setFlash('error', 'Skill name is required.');
        } elseif (strlen($skill) > 100) {
            setFlash('error', 'Skill name cannot exceed 100 characters.');
        } else {
            // Prevent duplicates for this user
            $check = $pdo->prepare('SELECT id FROM skills WHERE user_id=? AND skill_name=?');
            $check->execute([$userId, $skill]);
            if ($check->fetch()) {
                setFlash('warning', '"' . e($skill) . '" is already in your skills list.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO skills (user_id, skill_name) VALUES (?,?)');
                $stmt->execute([$userId, $skill]);
                setFlash('success', 'Skill "' . e($skill) . '" added.');
            }
        }

    } elseif ($action === 'edit') {
        $id    = (int)($_POST['id']         ?? 0);
        $skill = trim($_POST['skill_name']  ?? '');
        if (empty($skill)) {
            setFlash('error', 'Skill name cannot be empty.');
        } else {
            $stmt = $pdo->prepare('UPDATE skills SET skill_name=? WHERE id=? AND user_id=?');
            $stmt->execute([$skill, $id, $userId]);
            setFlash('success', 'Skill updated.');
        }

    } elseif ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM skills WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Skill removed.');
    }

    header('Location: skills.php');
    exit;
}

// ── Fetch ─────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM skills WHERE user_id=? ORDER BY skill_name ASC');
$stmt->execute([$userId]);
$skills = $stmt->fetchAll();
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
                    <h5 class="mb-1">Skills</h5>
                    <p class="text-muted mb-0">Showcase your technical and professional skills.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Skill
                </button>
            </div>

            <!-- Skills tag cloud / grid -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <?php if (empty($skills)): ?>
                    <div class="empty-state">
                        <i class="bi bi-lightning-charge display-4"></i>
                        <h6 class="mt-3">No skills added yet</h6>
                        <p class="text-muted">Add your technical and professional skills.</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i>Add First Skill
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="skills-cloud">
                        <?php foreach ($skills as $skill): ?>
                        <div class="skill-tag">
                            <span><?= e($skill['skill_name']) ?></span>
                            <div class="skill-actions">
                                <button class="skill-btn-edit"
                                        onclick="openEdit(<?= $skill['id'] ?>,'<?= e(addslashes($skill['skill_name'])) ?>')"
                                        title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="skill-btn-delete"
                                        onclick="confirmDelete(<?= $skill['id'] ?>,'<?= e(addslashes($skill['skill_name'])) ?>')"
                                        title="Delete">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <?= count($skills) ?> skill<?= count($skills) !== 1 ? 's' : '' ?> listed.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick-add inline form -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Quick Add Skill</h6>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="add">
                        <input type="text" class="form-control" name="skill_name"
                               placeholder="Enter skill name (e.g. Python, SPSS, Research Methods...)"
                               maxlength="100" required>
                        <button type="submit" class="btn btn-primary btn-portal flex-shrink-0">
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-lightning-charge me-2"></i>Add Skill</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-medium">Skill Name *</label>
                    <input type="text" class="form-control" name="skill_name"
                           placeholder="e.g. Python, Machine Learning, SPSS" required maxlength="100">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Skill</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-medium">Skill Name *</label>
                    <input type="text" class="form-control" name="skill_name" id="editSkillName"
                           required maxlength="100">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">Save</button>
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
function openEdit(id, name) {
    document.getElementById('editId').value        = id;
    document.getElementById('editSkillName').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
function confirmDelete(id, name) {
    if (confirm('Remove skill "' + name + '"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>
