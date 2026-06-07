<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$pageTitle = 'Dashboard';

// ── Key metrics ───────────────────────────────────────────────
$totalStaff   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$activeJobs   = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active'")->fetchColumn();
$totalApps    = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingApps  = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$reviewApps   = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='under_review'")->fetchColumn();
$acceptedApps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn();
$rejectedApps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();

// New staff this month
$newStaffMonth = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'
    AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();

// Apps this week
$appsThisWeek = (int)$pdo->query("SELECT COUNT(*) FROM applications
    WHERE applied_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// Applications by status for progress bars
$statusBreakdown = [];
if ($totalApps > 0) {
    $statusBreakdown = [
        'pending'      => $pendingApps,
        'under_review' => $reviewApps,
        'accepted'     => $acceptedApps,
        'rejected'     => $rejectedApps,
    ];
}

// Recent 8 applications
$recentApps = $pdo->query(
    "SELECT a.id, a.status, a.applied_at,
            j.title AS job_title, j.department,
            u.email,
            CONCAT(COALESCE(sp.first_name,''), ' ', COALESCE(sp.last_name,'')) AS full_name
     FROM applications a
     JOIN users u ON u.id = a.user_id
     JOIN jobs j  ON j.id = a.job_id
     LEFT JOIN staff_profiles sp ON sp.user_id = a.user_id
     ORDER BY a.applied_at DESC LIMIT 8"
)->fetchAll();

// Top 5 jobs by application count
$topJobs = $pdo->query(
    "SELECT j.title, j.department, COUNT(a.id) AS cnt
     FROM jobs j
     LEFT JOIN applications a ON a.job_id = j.id
     GROUP BY j.id ORDER BY cnt DESC LIMIT 5"
)->fetchAll();
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

            <!-- Welcome banner -->
            <div class="welcome-banner mb-4">
                <div>
                    <h4 class="mb-1">Admin Dashboard</h4>
                    <p class="mb-0 opacity-75">American University of Madaba — Employment Portal Overview</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="applications.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-clipboard-data me-1"></i>Applications
                    </a>
                    <a href="reports.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-bar-chart-line me-1"></i>Reports
                    </a>
                </div>
            </div>

            <!-- ── Stat cards ── -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $totalStaff ?></div>
                            <div class="stat-label">Total Academic Staff</div>
                        </div>
                        <small class="stat-link">
                            <i class="bi bi-plus-circle me-1"></i><?= $newStaffMonth ?> joined this month
                        </small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon-wrap"><i class="bi bi-briefcase-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $activeJobs ?></div>
                            <div class="stat-label">Active Job Listings</div>
                        </div>
                        <small class="stat-link">Open positions</small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="stat-icon-wrap"><i class="bi bi-send-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $totalApps ?></div>
                            <div class="stat-label">Total Applications</div>
                        </div>
                        <small class="stat-link">
                            <i class="bi bi-calendar-week me-1"></i><?= $appsThisWeek ?> this week
                        </small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon-wrap"><i class="bi bi-clock-history"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $pendingApps ?></div>
                            <div class="stat-label">Pending Review</div>
                        </div>
                        <?php if ($pendingApps > 0): ?>
                        <a href="applications.php?status=pending" class="stat-link">
                            Review now <i class="bi bi-arrow-right"></i>
                        </a>
                        <?php else: ?>
                        <small class="stat-link">All caught up!</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Row 2 ── -->
            <div class="row g-4 mb-4">

                <!-- Application status breakdown -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header-custom">
                            <i class="bi bi-pie-chart me-2"></i>Applications by Status
                        </div>
                        <div class="card-body py-4">
                            <?php if ($totalApps === 0): ?>
                            <p class="text-muted text-center py-3">No applications yet.</p>
                            <?php else:
                                $statusDefs = [
                                    'pending'      => ['Pending',      'bg-warning', $pendingApps],
                                    'under_review' => ['Under Review', 'bg-info',    $reviewApps],
                                    'accepted'     => ['Accepted',     'bg-success', $acceptedApps],
                                    'rejected'     => ['Rejected',     'bg-danger',  $rejectedApps],
                                ];
                                foreach ($statusDefs as $key => [$label, $color, $cnt]):
                                    $pct = $totalApps > 0 ? round($cnt / $totalApps * 100) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-medium small"><?= $label ?></span>
                                    <span class="fw-bold"><?= $cnt ?>
                                        <span class="text-muted fw-normal">(<?= $pct ?>%)</span>
                                    </span>
                                </div>
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar <?= $color ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>

                            <div class="mt-4 d-flex gap-2 flex-wrap">
                                <a href="applications.php" class="btn btn-sm btn-primary btn-portal">
                                    All Applications
                                </a>
                                <a href="applications.php?status=pending" class="btn btn-sm btn-outline-warning">
                                    Pending (<?= $pendingApps ?>)
                                </a>
                                <a href="applications.php?status=under_review" class="btn btn-sm btn-outline-info">
                                    Review (<?= $reviewApps ?>)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top jobs -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-trophy me-2"></i>Top Jobs by Applications</span>
                            <a href="reports.php" class="btn btn-sm btn-outline-light">Full Report</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($topJobs)): ?>
                            <p class="text-muted text-center py-4">No data yet.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Position</th>
                                            <th>Department</th>
                                            <th class="text-center">Applications</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($topJobs as $j): ?>
                                        <tr>
                                            <td class="fw-medium"><?= e($j['title']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= e($j['department'] ?? '—') ?></span></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $j['cnt'] ?></span>
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

            <!-- ── Recent applications ── -->
            <div class="card border-0 shadow-sm">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Recent Applications</span>
                    <a href="applications.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentApps)): ?>
                    <div class="empty-state"><i class="bi bi-inbox display-5"></i><p class="mt-2">No applications yet.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Applicant</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentApps as $app):
                                $name = trim($app['full_name']) ?: $app['email'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?= e($name) ?></div>
                                        <small class="text-muted"><?= e($app['email']) ?></small>
                                    </td>
                                    <td><?= e($app['job_title']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($app['department'] ?? '—') ?></span></td>
                                    <td><small><?= date('M j, Y', strtotime($app['applied_at'])) ?></small></td>
                                    <td><?= statusBadge($app['status']) ?></td>
                                    <td class="text-end">
                                        <a href="application_view.php?id=<?= $app['id'] ?>"
                                           class="btn btn-sm btn-primary">Review</a>
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
<?php include '../includes/admin_scripts.php'; ?>
</body>
</html>
