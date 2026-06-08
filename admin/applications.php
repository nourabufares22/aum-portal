<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$pageTitle = 'Application Management';

// -- Filters ---------------------------------------------------
$filterJob    = (int)(  $_GET['job']    ?? 0);
$filterDept   = trim(   $_GET['dept']   ?? '');
$filterStatus = trim(   $_GET['status'] ?? '');
$search       = trim(   $_GET['search'] ?? '');

// -- Pagination ------------------------------------------------
$perPage   = 20;
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $perPage;

// -- Build WHERE clause ----------------------------------------
$where  = ['1=1'];
$params = [];

if ($filterJob) {
    $where[]  = 'a.job_id = ?';
    $params[] = $filterJob;
}
if ($filterDept) {
    $where[]  = 'j.department = ?';
    $params[] = $filterDept;
}
if ($filterStatus && in_array($filterStatus, APP_STATUSES)) {
    $where[]  = 'a.status = ?';
    $params[] = $filterStatus;
}
if ($search) {
    $where[]  = '(sp.first_name LIKE ? OR sp.last_name LIKE ? OR u.email LIKE ?)';
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}

$whereStr = implode(' AND ', $where);
$baseSQL  = "FROM applications a
             JOIN users u        ON u.id = a.user_id
             JOIN jobs j         ON j.id = a.job_id
             LEFT JOIN staff_profiles sp ON sp.user_id = a.user_id
             WHERE $whereStr";

// Total count
$cntStmt = $pdo->prepare("SELECT COUNT(*) $baseSQL");
$cntStmt->execute($params);
$totalCount = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCount / $perPage));

// Data
$dataParams   = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare(
    "SELECT a.id, a.status, a.applied_at,
            j.id AS job_id, j.title AS job_title, j.department,
            u.id AS user_id, u.email,
            sp.first_name, sp.last_name, sp.department AS staff_dept
     $baseSQL
     ORDER BY a.applied_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->execute($dataParams);
$applications = $stmt->fetchAll();

// Status counts for tabs
$statusCounts = ['all' => $totalCount];
$scStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status");
foreach ($scStmt->fetchAll() as $r) $statusCounts[$r['status']] = (int)$r['cnt'];

// Jobs list for filter dropdown
$jobs = $pdo->query("SELECT id, title FROM jobs ORDER BY title")->fetchAll();

// Departments for filter
$depts = $pdo->query("SELECT DISTINCT department FROM jobs WHERE department IS NOT NULL ORDER BY department")
             ->fetchAll(PDO::FETCH_COLUMN);

// -- Current filter params string (for pagination links) -------
$filterQS = http_build_query(array_filter([
    'job'    => $filterJob    ?: null,
    'dept'   => $filterDept   ?: null,
    'status' => $filterStatus ?: null,
    'search' => $search       ?: null,
]));
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

            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Application Management</h5>
                    <p class="text-muted mb-0">Review, filter, and manage all job applications.</p>
                </div>
                <a href="reports.php" class="btn btn-outline-primary">
                    <i class="bi bi-bar-chart-line me-1"></i>View Reports
                </a>
            </div>

            <!-- -- Status filter tabs -- -->
            <ul class="nav nav-tabs mb-0">
                <?php
                $tabDefs = [
                    ''             => ['All',          $totalCount],
                    'pending'      => ['Pending',      $statusCounts['pending']      ?? 0],
                    'under_review' => ['Under Review', $statusCounts['under_review'] ?? 0],
                    'accepted'     => ['Accepted',     $statusCounts['accepted']     ?? 0],
                    'rejected'     => ['Rejected',     $statusCounts['rejected']     ?? 0],
                ];
                foreach ($tabDefs as $val => [$label, $cnt]):
                    $isActive = $filterStatus === $val;
                    $qs = http_build_query(array_filter(['job'=>$filterJob ?: null, 'dept'=>$filterDept ?: null, 'search'=>$search ?: null, 'status'=>$val ?: null]));
                ?>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="?<?= $qs ?>">
                        <?= $label ?>
                        <span class="badge ms-1 <?= $isActive ? 'bg-primary' : 'bg-secondary' ?>"><?= $cnt ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- -- Filter bar -- -->
            <div class="card border-0 shadow-sm border-top-0" style="border-radius:0 0 12px 12px">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <?php if ($filterStatus): ?>
                        <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
                        <?php endif; ?>

                        <div class="col-md-3">
                            <label class="form-label small fw-medium mb-1">Search Applicant</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                       placeholder="Name or email..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-medium mb-1">Filter by Job</label>
                            <select class="form-select form-select-sm" name="job">
                                <option value="">All Jobs</option>
                                <?php foreach ($jobs as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= $filterJob === (int)$j['id'] ? 'selected' : '' ?>>
                                    <?= e($j['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-medium mb-1">Filter by Department</label>
                            <select class="form-select form-select-sm" name="dept">
                                <option value="">All Departments</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?= e($d) ?>" <?= $filterDept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-portal btn-sm flex-grow-1">
                                <i class="bi bi-funnel me-1"></i>Apply Filters
                            </button>
                            <?php if ($search || $filterJob || $filterDept || $filterStatus): ?>
                            <a href="applications.php" class="btn btn-sm btn-outline-secondary" title="Clear">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- -- Applications table -- -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body p-0">
                    <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <i class="bi bi-clipboard display-4"></i>
                        <h6 class="mt-3">No applications found</h6>
                        <p class="text-muted">Try adjusting your filters.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 admin-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant</th>
                                    <th>Job Position</th>
                                    <th>Department</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($applications as $i => $app):
                                $name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                                if (empty(trim($name))) $name = $app['email'];
                            ?>
                                <tr>
                                    <td class="text-muted small"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="mini-avatar">
                                                <?= strtoupper(substr($name, 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= e($name) ?></div>
                                                <small class="text-muted"><?= e($app['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($app['job_title']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($app['department'] ?? '-') ?></span></td>
                                    <td><small class="text-muted"><?= date('M j, Y', strtotime($app['applied_at'])) ?></small></td>
                                    <td><?= statusBadge($app['status']) ?></td>
                                    <td class="text-end">
                                        <a href="application_view.php?id=<?= $app['id'] ?>"
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye me-1"></i>Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- -- Pagination -- -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalCount) ?>
                            of <?= $totalCount ?> applications
                        </small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= $filterQS ?>&page=<?= $page - 1 ?>">&lsaquo;</a>
                                </li>
                                <?php
                                $start = max(1, $page - 2);
                                $end   = min($totalPages, $page + 2);
                                for ($p = $start; $p <= $end; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $filterQS ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= $filterQS ?>&page=<?= $page + 1 ?>">&rsaquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include '../includes/admin_scripts.php'; ?>
</body>
</html>
