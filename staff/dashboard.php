<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$userId    = getCurrentUserId();

// Profile
$stmt = $pdo->prepare('SELECT * FROM staff_profiles WHERE user_id = ?');
$stmt->execute([$userId]);
$profile    = $stmt->fetch() ?: [];
$completion = profileCompletion($profile);
$firstName  = $profile['first_name'] ?? 'Staff Member';

// Module counts
$counts = [];
foreach (['qualifications','publications','experiences','skills','documents'] as $t) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM {$t} WHERE user_id = ?");
    $s->execute([$userId]);
    $counts[$t] = (int)$s->fetchColumn();
}

// Application status breakdown
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM applications WHERE user_id = ? GROUP BY status');
$stmt->execute([$userId]);
$apps = ['pending'=>0,'accepted'=>0,'rejected'=>0];
foreach ($stmt->fetchAll() as $r) $apps[$r['status']] = $r['cnt'];
$totalApps = array_sum($apps);

// Recent active jobs (limit 6)
$recentJobs = $pdo->query("SELECT * FROM jobs WHERE status='active' ORDER BY created_at DESC LIMIT 6")->fetchAll();
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

            <!-- Welcome banner -->
            <div class="welcome-banner mb-4">
                <div>
                    <h4 class="mb-1">Welcome back, <?= e($firstName) ?>!</h4>
                    <p class="mb-0 text-muted">Here's an overview of your academic profile.</p>
                </div>
                <a href="profile.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Profile
                </a>
            </div>

            <!-- -- Stat Cards ------------------------------- -->
            <div class="row g-4 mb-4">

                <!-- Profile completion -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $completion ?>%</div>
                            <div class="stat-label">Profile Complete</div>
                        </div>
                        <div class="stat-progress">
                            <div class="progress mt-2" style="height:4px;background:rgba(255,255,255,.3)">
                                <div class="progress-bar bg-white" style="width:<?= $completion ?>%"></div>
                            </div>
                            <?php if ($completion < 100): ?>
                            <small><a href="profile.php" class="text-white-50">Complete your profile -></a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Qualifications -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon-wrap"><i class="bi bi-award-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $counts['qualifications'] ?></div>
                            <div class="stat-label">Qualifications</div>
                        </div>
                        <a href="qualifications.php" class="stat-link">Manage <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Publications -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="stat-icon-wrap"><i class="bi bi-journal-richtext"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $counts['publications'] ?></div>
                            <div class="stat-label">Publications</div>
                        </div>
                        <a href="publications.php" class="stat-link">Manage <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Skills -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon-wrap"><i class="bi bi-lightning-charge-fill"></i></div>
                        <div class="stat-body">
                            <div class="stat-value"><?= $counts['skills'] ?></div>
                            <div class="stat-label">Skills</div>
                        </div>
                        <a href="skills.php" class="stat-link">Manage <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- -- Second row ------------------------------- -->
            <div class="row g-4">

                <!-- Application summary -->
                <div class="col-xl-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi bi-clipboard-check me-2"></i>My Applications
                        </div>
                        <div class="card-body py-3">
                            <div class="app-status-row pending-row">
                                <div class="app-status-icon-wrap status-pending">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="app-status-info">
                                    <span class="app-count"><?= $apps['pending'] ?></span>
                                    <span class="app-label">Pending</span>
                                </div>
                            </div>
                            <div class="app-status-row">
                                <div class="app-status-icon-wrap status-accepted">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="app-status-info">
                                    <span class="app-count"><?= $apps['accepted'] ?></span>
                                    <span class="app-label">Accepted</span>
                                </div>
                            </div>
                            <div class="app-status-row">
                                <div class="app-status-icon-wrap status-rejected">
                                    <i class="bi bi-x-circle-fill"></i>
                                </div>
                                <div class="app-status-info">
                                    <span class="app-count"><?= $apps['rejected'] ?></span>
                                    <span class="app-label">Rejected</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                            <a href="applications.php" class="btn btn-outline-primary w-100 btn-sm">
                                View All Applications (<?= $totalApps ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi bi-grid me-2"></i>Quick Actions
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <?php
                                $quickLinks = [
                                    ['href'=>'qualifications.php','icon'=>'bi-award','label'=>'Add Qualification','color'=>'blue'],
                                    ['href'=>'publications.php','icon'=>'bi-journal-plus','label'=>'Add Publication','color'=>'green'],
                                    ['href'=>'experience.php','icon'=>'bi-briefcase','label'=>'Add Experience','color'=>'purple'],
                                    ['href'=>'documents.php','icon'=>'bi-cloud-upload','label'=>'Upload Document','color'=>'orange'],
                                    ['href'=>'jobs.php','icon'=>'bi-search','label'=>'Browse Jobs','color'=>'primary'],
                                    ['href'=>'skills.php','icon'=>'bi-plus-circle','label'=>'Add Skill','color'=>'teal'],
                                ];
                                foreach ($quickLinks as $ql): ?>
                                <div class="col-6">
                                    <a href="<?= $ql['href'] ?>" class="quick-link-card">
                                        <i class="bi <?= $ql['icon'] ?>"></i>
                                        <span><?= $ql['label'] ?></span>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile summary -->
                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi bi-bar-chart me-2"></i>Profile Summary
                        </div>
                        <div class="card-body">
                            <?php
                            $summaryItems = [
                                ['label'=>'Qualifications', 'count'=>$counts['qualifications'], 'icon'=>'bi-award', 'color'=>'#1a56db'],
                                ['label'=>'Publications',   'count'=>$counts['publications'],   'icon'=>'bi-journal-text','color'=>'#057a55'],
                                ['label'=>'Experiences',    'count'=>$counts['experiences'],    'icon'=>'bi-briefcase', 'color'=>'#7e3af2'],
                                ['label'=>'Skills',         'count'=>$counts['skills'],         'icon'=>'bi-lightning-charge','color'=>'#c27803'],
                                ['label'=>'Documents',      'count'=>$counts['documents'],      'icon'=>'bi-folder2-open','color'=>'#e3a008'],
                            ];
                            foreach ($summaryItems as $s): ?>
                            <div class="summary-row">
                                <div class="summary-icon" style="color:<?= $s['color'] ?>">
                                    <i class="bi <?= $s['icon'] ?>"></i>
                                </div>
                                <div class="summary-label"><?= $s['label'] ?></div>
                                <div class="summary-count"><?= $s['count'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- -- Recent Jobs ------------------------------ -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-briefcase me-2"></i>Recent Job Opportunities</span>
                    <a href="jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentJobs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-5 d-block mb-2"></i>
                        No active job opportunities at the moment.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Deadline</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentJobs as $job):
                                    $dl = $job['deadline'] ? strtotime($job['deadline']) : null;
                                    $soon = $dl && ($dl - time()) < 7 * 86400 && $dl > time();
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($job['title']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($job['department'] ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($dl): ?>
                                        <span class="<?= $soon ? 'text-danger fw-semibold' : '' ?>">
                                            <?= $soon ? '<i class="bi bi-exclamation-circle me-1"></i>' : '' ?>
                                            <?= date('M j, Y', $dl) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">Open</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="jobs.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.content-area -->
    </div><!-- /.main-content -->
</div><!-- /.wrapper -->

<?php include '../includes/scripts.php'; ?>
</body>
</html>
