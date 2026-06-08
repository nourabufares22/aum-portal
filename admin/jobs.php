<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$pageTitle = 'Job Management';

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_job') {
        $title       = trim($_POST['title']        ?? '');
        $department  = trim($_POST['department']   ?? '');
        $description = trim($_POST['description']  ?? '');
        $requirements= trim($_POST['requirements'] ?? '');
        $deadline    = trim($_POST['deadline']      ?? '') ?: null;
        $status      = ($_POST['status'] ?? 'active') === 'closed' ? 'closed' : 'active';

        if (empty($title)) {
            setFlash('error', 'Job title is required.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO jobs (title, department, description, requirements, deadline, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$title, $department ?: null, $description ?: null,
                            $requirements ?: null, $deadline, $status]);
            setFlash('success', 'Job "' . $title . '" created successfully.');
        }
        header('Location: jobs.php');
        exit;
    }

    if ($action === 'edit_job') {
        $id          = (int)($_POST['job_id']       ?? 0);
        $title       = trim($_POST['title']         ?? '');
        $department  = trim($_POST['department']    ?? '');
        $description = trim($_POST['description']   ?? '');
        $requirements= trim($_POST['requirements']  ?? '');
        $deadline    = trim($_POST['deadline']       ?? '') ?: null;
        $status      = ($_POST['status'] ?? 'active') === 'closed' ? 'closed' : 'active';

        if (!$id || empty($title)) {
            setFlash('error', 'Invalid request or missing title.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE jobs SET title=?, department=?, description=?, requirements=?,
                 deadline=?, status=? WHERE id=?'
            );
            $stmt->execute([$title, $department ?: null, $description ?: null,
                            $requirements ?: null, $deadline, $status, $id]);
            setFlash('success', 'Job updated successfully.');
        }
        header('Location: jobs.php');
        exit;
    }

    if ($action === 'delete_job') {
        $id = (int)($_POST['job_id'] ?? 0);
        if ($id) {
            // Applications cascade-delete via FK
            $pdo->prepare('DELETE FROM jobs WHERE id=?')->execute([$id]);
            setFlash('success', 'Job deleted.');
        }
        header('Location: jobs.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['job_id'] ?? 0);
        if ($id) {
            $pdo->prepare(
                "UPDATE jobs SET status = IF(status='active','closed','active') WHERE id=?"
            )->execute([$id]);
            setFlash('success', 'Job status updated.');
        }
        header('Location: jobs.php');
        exit;
    }
}

// ── Fetch all jobs ────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'all';
$sql = "SELECT j.*, COUNT(a.id) AS app_count
        FROM jobs j
        LEFT JOIN applications a ON a.job_id = j.id";
if ($filterStatus === 'active')  $sql .= " WHERE j.status='active'";
if ($filterStatus === 'closed')  $sql .= " WHERE j.status='closed'";
$sql .= " GROUP BY j.id ORDER BY j.created_at DESC";

$jobs = $pdo->query($sql)->fetchAll();

$totalJobs  = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$activeJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active'")->fetchColumn();
$closedJobs = $totalJobs - $activeJobs;

// Pre-load job for edit modal if ?edit=id
$editJob = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $editJob = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/admin_head.php'; ?>
<body>
<div class="wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include '../includes/admin_navbar.php'; ?>
        <div class="content-area">
            <?php displayFlash(); ?>

            <!-- Page header -->
            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Job Management</h5>
                    <p class="text-muted mb-0">Create, edit, and manage job opportunities for academic staff.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#addJobModal">
                    <i class="bi bi-plus-lg me-2"></i>Add New Job
                </button>
            </div>

            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon-wrap"><i class="bi bi-briefcase-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $totalJobs ?></div>
                            <div class="stat-label">Total Jobs</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card stat-green">
                        <div class="stat-icon-wrap"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $activeJobs ?></div>
                            <div class="stat-label">Active Listings</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon-wrap"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $closedJobs ?></div>
                            <div class="stat-label">Closed Listings</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter tabs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= $filterStatus === 'all' ? 'active' : '' ?>"
                               href="jobs.php">All <span class="badge bg-secondary ms-1"><?= $totalJobs ?></span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $filterStatus === 'active' ? 'active' : '' ?>"
                               href="jobs.php?status=active">Active <span class="badge bg-success ms-1"><?= $activeJobs ?></span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $filterStatus === 'closed' ? 'active' : '' ?>"
                               href="jobs.php?status=closed">Closed <span class="badge bg-secondary ms-1"><?= $closedJobs ?></span></a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($jobs)): ?>
                    <div class="empty-state py-5">
                        <i class="bi bi-briefcase display-4"></i>
                        <h6 class="mt-3">No jobs found</h6>
                        <p class="text-muted">Click "Add New Job" to create the first listing.</p>
                        <button class="btn btn-primary btn-portal btn-sm" data-bs-toggle="modal" data-bs-target="#addJobModal">
                            <i class="bi bi-plus-lg me-1"></i>Add New Job
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Job Title</th>
                                    <th>Department</th>
                                    <th>Deadline</th>
                                    <th class="text-center">Applications</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($jobs as $i => $job):
                                $dl        = $job['deadline'] ? strtotime($job['deadline']) : null;
                                $isPast    = $dl && $dl < time();
                                $isActive  = $job['status'] === 'active';
                            ?>
                                <tr>
                                    <td class="text-muted small"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="fw-medium"><?= e($job['title']) ?></div>
                                        <small class="text-muted">Added <?= date('M j, Y', strtotime($job['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($job['department']): ?>
                                        <span class="badge bg-light text-dark border"><?= e($job['department']) ?></span>
                                        <?php else: ?>
                                        <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dl): ?>
                                        <small class="<?= $isPast ? 'text-danger' : 'text-muted' ?>">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('M j, Y', $dl) ?>
                                            <?= $isPast ? ' <span class="badge bg-danger-subtle text-danger">Expired</span>' : '' ?>
                                        </small>
                                        <?php else: ?>
                                        <small class="text-muted">Open</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($job['app_count'] > 0): ?>
                                        <a href="applications.php?job=<?= $job['id'] ?>" class="badge bg-primary text-decoration-none">
                                            <?= $job['app_count'] ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isActive): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <!-- Edit -->
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($job), ENT_QUOTES) ?>)"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Toggle status -->
                                            <form method="POST" class="d-inline"
                                                  data-confirm="<?= $isActive ? 'Close this job listing? Staff will no longer see it.' : 'Re-open this job listing?' ?>">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-sm <?= $isActive ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                        title="<?= $isActive ? 'Close' : 'Reopen' ?>">
                                                    <i class="bi <?= $isActive ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <form method="POST" class="d-inline"
                                                  data-confirm="Delete this job? All associated applications will also be deleted.">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete_job">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content-area -->
    </div><!-- /main-content -->
</div><!-- /wrapper -->

<!-- ── Add Job Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="addJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--primary);color:#fff">
                <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Add New Job</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_job">
                <div class="modal-body">
                    <?php include '_job_form_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-plus-lg me-1"></i>Create Job
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Edit Job Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="editJobModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--primary);color:#fff">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Job</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editJobForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit_job">
                <input type="hidden" name="job_id" id="editJobId">
                <div class="modal-body" id="editJobBody">
                    <?php
                    // Render the same fields but with empty defaults — JS fills them
                    $jobFormDefaults = [];
                    include '_job_form_fields.php';
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/admin_scripts.php'; ?>
<script>
function openEditModal(job) {
    document.getElementById('editJobId').value          = job.id;
    document.getElementById('edit_title').value         = job.title       || '';
    document.getElementById('edit_department').value    = job.department  || '';
    document.getElementById('edit_description').value   = job.description || '';
    document.getElementById('edit_requirements').value  = job.requirements|| '';
    document.getElementById('edit_deadline').value      = job.deadline    || '';
    document.getElementById('edit_status').value        = job.status      || 'active';
    new bootstrap.Modal(document.getElementById('editJobModal')).show();
}
</script>
</body>
</html>
