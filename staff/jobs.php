<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Job Opportunities';
$userId    = getCurrentUserId();

// ── Handle application submission ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    $jobId       = (int)($_POST['job_id']      ?? 0);
    $coverLetter = trim($_POST['cover_letter'] ?? '');

    // Verify job exists and is active
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id=? AND status='active'");
    $stmt->execute([$jobId]);
    if (!$stmt->fetch()) {
        setFlash('error', 'Job not found or no longer accepting applications.');
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO applications (user_id,job_id,cover_letter) VALUES (?,?,?)');
            $stmt->execute([$userId, $jobId, $coverLetter ?: null]);
            setFlash('success', 'Application submitted successfully!');
        } catch (PDOException $e) {
            // Unique constraint violation = already applied
            setFlash('warning', 'You have already applied to this position.');
        }
    }
    header('Location: jobs.php');
    exit;
}

// ── Fetch jobs ────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$dept   = trim($_GET['dept']   ?? '');

$sql    = "SELECT j.*,
                  (SELECT id FROM applications WHERE user_id=? AND job_id=j.id) AS applied_id
           FROM jobs j WHERE j.status='active'";
$params = [$userId];

if (!empty($search)) {
    $sql    .= ' AND (j.title LIKE ? OR j.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($dept)) {
    $sql    .= ' AND j.department = ?';
    $params[] = $dept;
}
$sql .= ' ORDER BY j.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Departments for filter
$depts = $pdo->query("SELECT DISTINCT department FROM jobs WHERE status='active' AND department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Detail view — show any job (active or closed) so applied staff can review their application
$selectedJob = null;
if (!empty($_GET['id'])) {
    $jid  = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT j.*,
                                   (SELECT id FROM applications WHERE user_id=? AND job_id=j.id) AS applied_id
                            FROM jobs j WHERE j.id=?");
    $stmt->execute([$userId, $jid]);
    $selectedJob = $stmt->fetch();
}
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

            <?php if ($selectedJob): ?>
            <!-- ── Job Detail View ──────────────────────────── -->
            <div class="mb-3">
                <a href="jobs.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Job List
                </a>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start gap-3 mb-4">
                                <div class="job-detail-icon">
                                    <i class="bi bi-briefcase-fill"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1"><?= e($selectedJob['title']) ?></h4>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-building me-1"></i><?= e($selectedJob['department'] ?? 'AUM') ?>
                                        <?php if ($selectedJob['deadline']): ?>
                                        &nbsp;&bull;&nbsp;
                                        <i class="bi bi-calendar me-1"></i>Deadline: <strong><?= date('F j, Y', strtotime($selectedJob['deadline'])) ?></strong>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <h6 class="fw-semibold mb-2">About the Role</h6>
                            <p class="text-muted lh-lg"><?= nl2br(e($selectedJob['description'] ?? '')) ?></p>

                            <?php if (!empty($selectedJob['requirements'])): ?>
                            <h6 class="fw-semibold mb-2 mt-4">Requirements</h6>
                            <ul class="text-muted">
                                <?php foreach (explode("\n", $selectedJob['requirements']) as $req):
                                    $req = trim($req);
                                    if ($req): ?>
                                <li class="mb-1"><?= e($req) ?></li>
                                <?php endif; endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Apply card -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top:80px">
                        <div class="card-body p-4">
                            <?php if ($selectedJob['applied_id']): ?>
                            <div class="text-center py-3">
                                <div class="mb-3" style="font-size:3rem;color:#057a55">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <h6>Application Submitted</h6>
                                <p class="text-muted small">You have already applied to this position.</p>
                                <a href="applications.php" class="btn btn-outline-primary btn-sm">
                                    Track Application
                                </a>
                            </div>
                            <?php elseif ($selectedJob['deadline'] && strtotime($selectedJob['deadline']) < time()): ?>
                            <div class="text-center py-3">
                                <div class="mb-3" style="font-size:3rem;color:#970000">
                                    <i class="bi bi-x-circle-fill"></i>
                                </div>
                                <h6>Application Closed</h6>
                                <p class="text-muted small">The deadline for this position has passed.</p>
                            </div>
                            <?php else: ?>
                            <h6 class="fw-semibold mb-3">Apply for this Position</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="apply">
                                <input type="hidden" name="job_id" value="<?= $selectedJob['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Cover Letter <span class="text-muted">(optional)</span></label>
                                    <textarea class="form-control" name="cover_letter" rows="6"
                                              placeholder="Write a brief cover letter explaining your suitability for this role..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-portal w-100">
                                    <i class="bi bi-send me-2"></i>Submit Application
                                </button>
                            </form>
                            <p class="text-muted small mt-3 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Ensure your profile and documents are up to date before applying.
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ── Job List View ────────────────────────────── -->
            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Job Opportunities</h5>
                    <p class="text-muted mb-0">Browse and apply to active positions at AUM.</p>
                </div>
                <span class="badge bg-primary fs-6"><?= count($jobs) ?> Active</span>
            </div>

            <!-- Search & filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                       placeholder="Search positions..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="dept">
                                <option value="">All Departments</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?= e($d) ?>" <?= $dept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-portal w-100">Filter</button>
                            <?php if ($search || $dept): ?>
                            <a href="jobs.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-briefcase display-4"></i>
                        <h6 class="mt-3">No job opportunities found</h6>
                        <p class="text-muted">
                            <?= ($search || $dept) ? 'Try different search terms or clear filters.' : 'No active positions at the moment. Check back soon.' ?>
                        </p>
                        <?php if ($search || $dept): ?>
                        <a href="jobs.php" class="btn btn-outline-primary btn-sm">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($jobs as $job):
                    $dl   = $job['deadline'] ? strtotime($job['deadline']) : null;
                    $soon = $dl && $dl - time() < 7 * 86400 && $dl > time();
                    $past = $dl && $dl < time();
                ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 job-card <?= $past ? 'job-expired' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="job-icon-wrap">
                                    <i class="bi bi-briefcase-fill"></i>
                                </div>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if ($job['applied_id']): ?>
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-check me-1"></i>Applied
                                    </span>
                                    <?php elseif ($past): ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Closed</span>
                                    <?php elseif ($soon): ?>
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="bi bi-exclamation me-1"></i>Closing Soon
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <h6 class="fw-semibold mb-1"><?= e($job['title']) ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-building me-1"></i><?= e($job['department'] ?? 'AUM') ?>
                            </p>

                            <p class="text-muted small mb-3">
                                <?= e(mb_strimwidth($job['description'] ?? '', 0, 120, '…')) ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($dl): ?>
                                <small class="text-muted <?= $soon ? 'text-warning' : '' ?>">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= $past ? 'Closed ' : 'Due ' ?>
                                    <?= date('M j, Y', $dl) ?>
                                </small>
                                <?php else: ?>
                                <small class="text-muted">Open deadline</small>
                                <?php endif; ?>
                                <a href="jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; /* end list/detail toggle */ ?>

        </div>
    </div>
</div>

<?php include '../includes/scripts.php'; ?>
</body>
</html>
