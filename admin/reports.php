<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$pageTitle = 'Reports & Analytics';

// ── CSV export handler (runs before any HTML) ─────────────────
$exportType = trim($_GET['export'] ?? '');

if ($exportType) {
    // Build CSV data based on type
    $csvData    = [];
    $csvHeaders = [];
    $filename   = 'aum_report_' . $exportType . '_' . date('Y-m-d') . '.csv';

    switch ($exportType) {

        case 'total_staff':
            $csvHeaders = ['Email', 'Role', 'Registered Date'];
            $rows = $pdo->query("SELECT email, role, created_at FROM users WHERE role='staff' ORDER BY created_at DESC")->fetchAll();
            foreach ($rows as $r) $csvData[] = [e($r['email']), $r['role'], $r['created_at']];
            break;

        case 'staff_by_dept':
            $csvHeaders = ['Department', 'Staff Count'];
            $rows = $pdo->query("SELECT COALESCE(department,'Not Set') AS dept, COUNT(*) AS cnt
                                  FROM staff_profiles GROUP BY dept ORDER BY cnt DESC")->fetchAll();
            foreach ($rows as $r) $csvData[] = [$r['dept'], $r['cnt']];
            break;

        case 'jobs':
            $csvHeaders = ['Job Title', 'Department', 'Status', 'Deadline', 'Created'];
            $rows = $pdo->query("SELECT title, department, status, deadline, created_at FROM jobs ORDER BY created_at DESC")->fetchAll();
            foreach ($rows as $r) $csvData[] = [$r['title'], $r['department'], $r['status'], $r['deadline'], $r['created_at']];
            break;

        case 'apps_per_job':
            $csvHeaders = ['Job Title', 'Department', 'Total', 'Pending', 'Under Review', 'Accepted', 'Rejected'];
            $rows = $pdo->query(
                "SELECT j.title, j.department,
                        COUNT(a.id) AS total,
                        SUM(a.status='pending')       AS pending,
                        SUM(a.status='under_review')  AS under_review,
                        SUM(a.status='accepted')      AS accepted,
                        SUM(a.status='rejected')      AS rejected
                 FROM jobs j LEFT JOIN applications a ON a.job_id=j.id
                 GROUP BY j.id ORDER BY total DESC"
            )->fetchAll();
            foreach ($rows as $r) $csvData[] = [$r['title'],$r['department'],$r['total'],$r['pending'],$r['under_review'],$r['accepted'],$r['rejected']];
            break;

        case 'apps_by_status':
            $csvHeaders = ['Status', 'Count', 'Percentage'];
            $total = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
            $rows  = $pdo->query("SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status ORDER BY cnt DESC")->fetchAll();
            foreach ($rows as $r) {
                $pct = $total > 0 ? round($r['cnt'] / $total * 100, 1) : 0;
                $csvData[] = [ucwords(str_replace('_',' ',$r['status'])), $r['cnt'], $pct . '%'];
            }
            break;

        case 'accepted_rejected':
            $csvHeaders = ['Applicant Name', 'Email', 'Job Title', 'Department', 'Status', 'Applied Date'];
            $rows = $pdo->query(
                "SELECT CONCAT(COALESCE(sp.first_name,''),' ',COALESCE(sp.last_name,'')) AS name,
                        u.email, j.title, j.department, a.status, a.applied_at
                 FROM applications a
                 JOIN users u ON u.id=a.user_id
                 JOIN jobs j  ON j.id=a.job_id
                 LEFT JOIN staff_profiles sp ON sp.user_id=a.user_id
                 WHERE a.status IN ('accepted','rejected')
                 ORDER BY a.status, a.applied_at DESC"
            )->fetchAll();
            foreach ($rows as $r) $csvData[] = [trim($r['name']) ?: $r['email'], $r['email'], $r['title'], $r['department'], $r['status'], $r['applied_at']];
            break;

        case 'active_depts':
            $csvHeaders = ['Department', 'Applications', 'Staff Count'];
            $rows = $pdo->query(
                "SELECT COALESCE(sp.department,'Not Set') AS dept,
                        COUNT(a.id) AS apps,
                        COUNT(DISTINCT sp.user_id) AS staff
                 FROM applications a
                 JOIN staff_profiles sp ON sp.user_id=a.user_id
                 GROUP BY dept ORDER BY apps DESC"
            )->fetchAll();
            foreach ($rows as $r) $csvData[] = [$r['dept'], $r['apps'], $r['staff']];
            break;

        case 'recent_activity':
            $csvHeaders = ['Applicant', 'Email', 'Job Title', 'Status', 'Applied Date'];
            $rows = $pdo->query(
                "SELECT CONCAT(COALESCE(sp.first_name,''),' ',COALESCE(sp.last_name,'')) AS name,
                        u.email, j.title, a.status, a.applied_at
                 FROM applications a
                 JOIN users u ON u.id=a.user_id
                 JOIN jobs j  ON j.id=a.job_id
                 LEFT JOIN staff_profiles sp ON sp.user_id=a.user_id
                 ORDER BY a.applied_at DESC LIMIT 100"
            )->fetchAll();
            foreach ($rows as $r) $csvData[] = [trim($r['name']) ?: $r['email'], $r['email'], $r['title'], $r['status'], $r['applied_at']];
            break;

        default:
            http_response_code(400); exit('Invalid report type.');
    }

    // Output CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache'); header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, $csvHeaders);
    foreach ($csvData as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

// ── Load all report data (HTML mode) ──────────────────────────

// 1. Summary
$totalStaff   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn();
$totalJobs    = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$activeJobs   = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='active'")->fetchColumn();
$totalApps    = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$acceptedApps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn();
$rejectedApps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();
$acceptanceRate = $totalApps > 0 ? round($acceptedApps / $totalApps * 100, 1) : 0;

// 2. Staff by department
$staffByDept = $pdo->query(
    "SELECT COALESCE(department,'Not Set') AS dept, COUNT(*) AS cnt
     FROM staff_profiles GROUP BY dept ORDER BY cnt DESC"
)->fetchAll();

// 3. Applications per job
$appsPerJob = $pdo->query(
    "SELECT j.title, j.department, j.status AS job_status,
            COUNT(a.id) AS total,
            SUM(a.status='pending')      AS pending,
            SUM(a.status='under_review') AS under_review,
            SUM(a.status='accepted')     AS accepted,
            SUM(a.status='rejected')     AS rejected
     FROM jobs j LEFT JOIN applications a ON a.job_id=j.id
     GROUP BY j.id ORDER BY total DESC"
)->fetchAll();

// 4. Apps by status
$appsByStatus = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status ORDER BY cnt DESC"
)->fetchAll();

// 5. Active departments
$activeDepts = $pdo->query(
    "SELECT COALESCE(sp.department,'Not Set') AS dept,
            COUNT(a.id) AS apps,
            COUNT(DISTINCT sp.user_id) AS staff
     FROM applications a
     JOIN staff_profiles sp ON sp.user_id=a.user_id
     GROUP BY dept ORDER BY apps DESC LIMIT 10"
)->fetchAll();

// 6. Recent activity (last 15)
$recentActivity = $pdo->query(
    "SELECT CONCAT(COALESCE(sp.first_name,''),' ',COALESCE(sp.last_name,'')) AS name,
            u.email, j.title AS job_title, j.department, a.status, a.applied_at, a.id
     FROM applications a
     JOIN users u ON u.id=a.user_id
     JOIN jobs j  ON j.id=a.job_id
     LEFT JOIN staff_profiles sp ON sp.user_id=a.user_id
     ORDER BY a.applied_at DESC LIMIT 15"
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

            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Reports & Analytics</h5>
                    <p class="text-muted mb-0">American University of Madaba — Employment Portal Statistics</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </div>

            <!-- ── Summary stat cards ── -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-body"><div class="stat-value"><?= $totalStaff ?></div><div class="stat-label">Total Academic Staff</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon-wrap"><i class="bi bi-briefcase-fill"></i></div>
                        <div class="stat-body"><div class="stat-value"><?= $activeJobs ?> / <?= $totalJobs ?></div><div class="stat-label">Active / Total Jobs</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="stat-icon-wrap"><i class="bi bi-send-fill"></i></div>
                        <div class="stat-body"><div class="stat-value"><?= $totalApps ?></div><div class="stat-label">Total Applications</div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon-wrap"><i class="bi bi-percent"></i></div>
                        <div class="stat-body"><div class="stat-value"><?= $acceptanceRate ?>%</div><div class="stat-label">Acceptance Rate</div></div>
                    </div>
                </div>
            </div>

            <?php
            // Helper: export button
            function exportBtn(string $type, string $label): string {
                return '<a href="reports.php?export=' . urlencode($type) . '" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i>' . $label . '
                        </a>';
            }
            ?>

            <!-- ── Report 1: Staff by Department ── -->
            <div class="card border-0 shadow-sm mb-4 report-card" id="report-staff-dept">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-building me-2"></i>Staff by Department</span>
                    <?= exportBtn('staff_by_dept','Export CSV') ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>#</th><th>Department</th><th>Staff Count</th><th>Share</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($staffByDept as $i => $r):
                                $pct = $totalStaff > 0 ? round($r['cnt'] / $totalStaff * 100) : 0; ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td><?= e($r['dept']) ?></td>
                                <td><span class="badge bg-primary"><?= $r['cnt'] ?></span></td>
                                <td style="min-width:160px">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px">
                                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $pct ?>%</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($staffByDept)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No data</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Report 2: Applications per Job ── -->
            <div class="card border-0 shadow-sm mb-4 report-card" id="report-apps-job">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-briefcase me-2"></i>Applications per Job</span>
                    <?= exportBtn('apps_per_job','Export CSV') ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job Title</th><th>Department</th><th class="text-center">Total</th>
                                    <th class="text-center">Pending</th><th class="text-center">Review</th>
                                    <th class="text-center">Accepted</th><th class="text-center">Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($appsPerJob as $r): ?>
                            <tr>
                                <td class="fw-medium">
                                    <?= e($r['title']) ?>
                                    <?php if ($r['job_status'] === 'closed'): ?>
                                    <span class="badge bg-secondary ms-1">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($r['department'] ?? '—') ?></span></td>
                                <td class="text-center"><strong><?= $r['total'] ?></strong></td>
                                <td class="text-center"><span class="badge bg-warning text-dark"><?= $r['pending'] ?></span></td>
                                <td class="text-center"><span class="badge bg-info"><?= $r['under_review'] ?></span></td>
                                <td class="text-center"><span class="badge bg-success"><?= $r['accepted'] ?></span></td>
                                <td class="text-center"><span class="badge bg-danger"><?= $r['rejected'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Row: Apps by Status + Accepted vs Rejected ── -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 report-card">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pie-chart me-2"></i>Applications by Status</span>
                            <?= exportBtn('apps_by_status','Export CSV') ?>
                        </div>
                        <div class="card-body">
                            <?php
                            $statusColors = [
                                'pending'      => 'bg-warning',
                                'under_review' => 'bg-info',
                                'accepted'     => 'bg-success',
                                'rejected'     => 'bg-danger',
                            ];
                            foreach ($appsByStatus as $r):
                                $pct = $totalApps > 0 ? round($r['cnt'] / $totalApps * 100) : 0;
                                $clr = $statusColors[$r['status']] ?? 'bg-secondary';
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium"><?= ucwords(str_replace('_',' ',$r['status'])) ?></span>
                                    <span><?= $r['cnt'] ?> <span class="text-muted small">(<?= $pct ?>%)</span></span>
                                </div>
                                <div class="progress" style="height:10px">
                                    <div class="progress-bar <?= $clr ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($appsByStatus)): ?>
                            <p class="text-muted text-center">No data yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 report-card">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-check-x me-2"></i>Accepted vs Rejected</span>
                            <?= exportBtn('accepted_rejected','Export CSV') ?>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center mb-3">
                                <div class="col-6">
                                    <div class="accepted-rejected-box accepted-box">
                                        <div class="arb-value"><?= $acceptedApps ?></div>
                                        <div class="arb-label">Accepted</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="accepted-rejected-box rejected-box">
                                        <div class="arb-value"><?= $rejectedApps ?></div>
                                        <div class="arb-label">Rejected</div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($totalApps > 0): ?>
                            <div class="mt-3">
                                <label class="form-label small text-muted">Accepted Rate</label>
                                <div class="progress mb-1" style="height:12px">
                                    <div class="progress-bar bg-success" style="width:<?= $acceptanceRate ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $acceptanceRate ?>% of all reviewed applications</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Report: Most Active Departments ── -->
            <div class="card border-0 shadow-sm mb-4 report-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-graph-up me-2"></i>Most Active Departments</span>
                    <?= exportBtn('active_depts','Export CSV') ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>#</th><th>Department</th><th class="text-center">Staff</th><th class="text-center">Applications</th><th>Activity Bar</th></tr>
                            </thead>
                            <tbody>
                            <?php
                            $maxApps = !empty($activeDepts) ? max(array_column($activeDepts, 'apps')) : 1;
                            foreach ($activeDepts as $i => $r):
                                $barPct = $maxApps > 0 ? round($r['apps'] / $maxApps * 100) : 0;
                            ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td><?= e($r['dept']) ?></td>
                                <td class="text-center"><?= $r['staff'] ?></td>
                                <td class="text-center"><strong><?= $r['apps'] ?></strong></td>
                                <td style="min-width:160px">
                                    <div class="progress" style="height:8px">
                                        <div class="progress-bar bg-primary" style="width:<?= $barPct ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($activeDepts)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No activity data yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── Report: Recent Recruitment Activity ── -->
            <div class="card border-0 shadow-sm report-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-activity me-2"></i>Recent Recruitment Activity</span>
                    <?= exportBtn('recent_activity','Export CSV') ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentActivity)): ?>
                    <div class="empty-state"><i class="bi bi-inbox display-5"></i><p class="mt-2">No activity yet.</p></div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Applicant</th><th>Job Position</th><th>Department</th>
                                    <th>Applied</th><th>Status</th><th class="text-end">View</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentActivity as $r):
                                $name = trim($r['name']) ?: $r['email'];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= e($name) ?></div>
                                    <small class="text-muted"><?= e($r['email']) ?></small>
                                </td>
                                <td><?= e($r['job_title']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($r['department'] ?? '—') ?></span></td>
                                <td><small class="text-muted"><?= date('M j, Y', strtotime($r['applied_at'])) ?></small></td>
                                <td><?= statusBadge($r['status']) ?></td>
                                <td class="text-end">
                                    <a href="application_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
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
