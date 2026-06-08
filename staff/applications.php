<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'My Applications';
$userId    = getCurrentUserId();

// ── Handle withdraw ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    $appId = (int)($_POST['id'] ?? 0);
    // Only allow withdrawing pending applications
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id=? AND user_id=? AND status='pending'");
    $stmt->execute([$appId, $userId]);
    if ($stmt->rowCount()) {
        setFlash('success', 'Application withdrawn successfully.');
    } else {
        setFlash('error', 'Could not withdraw — application may no longer be pending.');
    }
    header('Location: applications.php');
    exit;
}

// ── Filter ────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'all';
$allowedStatuses = ['all','pending','under_review','accepted','rejected'];
if (!in_array($filterStatus, $allowedStatuses)) $filterStatus = 'all';

$sql = 'SELECT a.*, j.title AS job_title, j.department, j.deadline, j.status AS job_status
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        WHERE a.user_id = ?';
$params = [$userId];
if ($filterStatus !== 'all') {
    $sql    .= ' AND a.status = ?';
    $params[] = $filterStatus;
}
$sql .= ' ORDER BY a.applied_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Status counts
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM applications WHERE user_id=? GROUP BY status');
$stmt->execute([$userId]);
$counts = ['all'=>0,'pending'=>0,'under_review'=>0,'accepted'=>0,'rejected'=>0];
foreach ($stmt->fetchAll() as $r) {
    $counts[$r['status']] = (int)$r['cnt'];
    $counts['all'] += (int)$r['cnt'];
}

$statusConfig = [
    'pending'      => ['label'=>'Pending',      'badge'=>'bg-warning text-dark', 'icon'=>'bi-clock-history'],
    'under_review' => ['label'=>'Under Review', 'badge'=>'bg-info text-white',   'icon'=>'bi-search'],
    'accepted'     => ['label'=>'Accepted',     'badge'=>'bg-success',           'icon'=>'bi-check-circle-fill'],
    'rejected'     => ['label'=>'Rejected',     'badge'=>'bg-danger',            'icon'=>'bi-x-circle-fill'],
];
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
                    <h5 class="mb-1">My Applications</h5>
                    <p class="text-muted mb-0">Track the status of your job applications.</p>
                </div>
                <a href="jobs.php" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i>Browse Jobs
                </a>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md">
                    <a href="applications.php" class="text-decoration-none">
                        <div class="app-summary-card <?= $filterStatus==='all' ? 'active' : '' ?>">
                            <div class="app-summary-count"><?= $counts['all'] ?></div>
                            <div class="app-summary-label">Total</div>
                        </div>
                    </a>
                </div>
                <?php foreach (['pending','under_review','accepted','rejected'] as $s):
                    $cfg = $statusConfig[$s]; ?>
                <div class="col-6 col-md">
                    <a href="?status=<?= $s ?>" class="text-decoration-none">
                        <div class="app-summary-card app-summary-<?= $s ?> <?= $filterStatus===$s ? 'active' : '' ?>">
                            <div class="app-summary-count"><?= $counts[$s] ?></div>
                            <div class="app-summary-label"><?= $cfg['label'] ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filter tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $filterStatus==='all' ? 'active' : '' ?>" href="applications.php">
                        All <span class="badge bg-secondary ms-1"><?= $counts['all'] ?></span>
                    </a>
                </li>
                <?php foreach (['pending','under_review','accepted','rejected'] as $s):
                    $cfg = $statusConfig[$s]; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $filterStatus===$s ? 'active' : '' ?>"
                       href="?status=<?= $s ?>">
                        <?= $cfg['label'] ?>
                        <span class="badge bg-secondary ms-1"><?= $counts[$s] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Applications list -->
            <?php if (empty($applications)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-clipboard display-4"></i>
                        <h6 class="mt-3">
                            <?= $filterStatus==='all' ? 'No applications yet' : 'No ' . $filterStatus . ' applications' ?>
                        </h6>
                        <p class="text-muted">
                            <?= $filterStatus==='all' ? 'Browse available jobs and submit your first application.' : 'No applications with this status.' ?>
                        </p>
                        <?php if ($filterStatus==='all'): ?>
                        <a href="jobs.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i>Browse Jobs
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($applications as $app):
                    $cfg = $statusConfig[$app['status']];
                    $dl  = $app['deadline'] ? strtotime($app['deadline']) : null;
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm app-item-card">
                        <div class="card-body">
                            <div class="row align-items-center g-3">
                                <div class="col-md-5">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="app-job-icon">
                                            <i class="bi bi-briefcase-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= e($app['job_title']) ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-building me-1"></i><?= e($app['department'] ?? 'AUM') ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Applied on</small>
                                    <span><?= date('M j, Y', strtotime($app['applied_at'])) ?></span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge <?= $cfg['badge'] ?> px-3 py-2">
                                        <i class="bi <?= $cfg['icon'] ?> me-1"></i>
                                        <?= $cfg['label'] ?>
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="jobs.php?id=<?= $app['job_id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            View Job
                                        </a>
                                        <?php if ($app['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="confirmWithdraw(<?= $app['id'] ?>,'<?= e(addslashes($app['job_title'])) ?>')">
                                            Withdraw
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($app['cover_letter'])): ?>
                            <div class="mt-3 pt-3 border-top">
                                <p class="text-muted small mb-1 fw-medium">Cover Letter:</p>
                                <p class="text-muted small mb-0 cover-letter-preview">
                                    <?= e(mb_strimwidth($app['cover_letter'], 0, 200, '…')) ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <?php if ($app['status'] === 'accepted'): ?>
                            <div class="mt-3 alert alert-success py-2 mb-0">
                                <i class="bi bi-trophy-fill me-2"></i>
                                <strong>Congratulations!</strong> Your application has been accepted. AUM HR will contact you shortly.
                            </div>
                            <?php elseif ($app['status'] === 'under_review'): ?>
                            <div class="mt-3 alert alert-info py-2 mb-0">
                                <i class="bi bi-search me-2"></i>
                                Your application is currently being reviewed by the hiring team.
                            </div>
                            <?php elseif ($app['status'] === 'rejected'): ?>
                            <div class="mt-3 alert alert-secondary py-2 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Thank you for applying. Unfortunately this position has been filled.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Withdraw form -->
<form id="withdrawForm" method="POST" class="d-none">
    <input type="hidden" name="action" value="withdraw">
    <input type="hidden" name="id" id="withdrawId">
</form>

<?php include '../includes/scripts.php'; ?>
<script>
function confirmWithdraw(id, title) {
    if (confirm('Withdraw your application for:\n"' + title + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('withdrawId').value = id;
        document.getElementById('withdrawForm').submit();
    }
}
</script>
</body>
</html>
