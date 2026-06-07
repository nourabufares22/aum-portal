<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'My Profile';
$userId    = getCurrentUserId();

// Load profile & user email
$stmt = $pdo->prepare('SELECT sp.*, u.email
                        FROM users u
                        LEFT JOIN staff_profiles sp ON sp.user_id = u.id
                        WHERE u.id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch() ?: [];

// ── Handle form submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name'  => trim($_POST['first_name']  ?? ''),
        'last_name'   => trim($_POST['last_name']   ?? ''),
        'phone'       => trim($_POST['phone']       ?? ''),
        'department'  => trim($_POST['department']  ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'linkedin'    => trim($_POST['linkedin']    ?? ''),
    ];

    // Validate LinkedIn URL if provided
    if (!empty($fields['linkedin']) && !filter_var($fields['linkedin'], FILTER_VALIDATE_URL)) {
        setFlash('error', 'Please enter a valid LinkedIn URL.');
        header('Location: profile.php');
        exit;
    }

    // Check if profile row exists
    $stmt = $pdo->prepare('SELECT id FROM staff_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $stmt = $pdo->prepare('UPDATE staff_profiles
                                SET first_name=?, last_name=?, phone=?, department=?,
                                    nationality=?, linkedin=?
                                WHERE user_id=?');
        $stmt->execute([
            $fields['first_name'], $fields['last_name'], $fields['phone'],
            $fields['department'], $fields['nationality'],
            $fields['linkedin'], $userId
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO staff_profiles
                                (user_id,first_name,last_name,phone,department,nationality,linkedin)
                                VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $userId, $fields['first_name'], $fields['last_name'], $fields['phone'],
            $fields['department'], $fields['nationality'],
            $fields['linkedin']
        ]);
    }

    setFlash('success', 'Profile updated successfully.');
    header('Location: profile.php');
    exit;
}

$completion = profileCompletion($profile);
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

            <div class="row g-4">

                <!-- Left: Profile card -->
                <div class="col-xl-4 col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4">
                        <div class="profile-avatar mx-auto mb-3">
                            <?= strtoupper(substr(($profile['first_name'] ?? 'S'), 0, 1) . substr(($profile['last_name'] ?? ''), 0, 1)) ?>
                        </div>
                        <h5 class="mb-0">
                            <?= e(trim(($profile['first_name']??'') . ' ' . ($profile['last_name']??''))) ?: 'Your Name' ?>
                        </h5>
                        <p class="text-muted small mb-1"><?= e($profile['department'] ?? 'Department not set') ?></p>
                        <p class="text-muted small mb-3"><?= e($profile['email'] ?? '') ?></p>

                        <!-- Completion ring -->
                        <div class="completion-ring mb-2">
                            <svg viewBox="0 0 36 36" class="completion-svg">
                                <path d="M18 2.0845 a15.9155 15.9155 0 0 1 0 31.831 a15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none" stroke="#e9ecef" stroke-width="3"/>
                                <path d="M18 2.0845 a15.9155 15.9155 0 0 1 0 31.831 a15.9155 15.9155 0 0 1 0 -31.831"
                                      fill="none" stroke="#970000" stroke-width="3"
                                      stroke-dasharray="<?= $completion ?>, 100"/>
                            </svg>
                            <span class="completion-pct"><?= $completion ?>%</span>
                        </div>
                        <small class="text-muted">Profile Completion</small>

                        <?php if (!empty($profile['linkedin'])): ?>
                        <div class="mt-3">
                            <a href="<?= e($profile['linkedin']) ?>" target="_blank" rel="noopener"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-linkedin me-1"></i>LinkedIn Profile
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick stats below avatar card -->
                    <div class="card border-0 shadow-sm mt-3 p-3">
                        <h6 class="text-muted mb-3 small text-uppercase">Profile Details</h6>
                        <?php
                        $details = [
                            ['icon'=>'bi-telephone','label'=>'Phone',       'value'=>$profile['phone']       ?? null],
                            ['icon'=>'bi-building', 'label'=>'Department',  'value'=>$profile['department']  ?? null],
                            ['icon'=>'bi-globe',    'label'=>'Nationality', 'value'=>$profile['nationality'] ?? null],
                        ];
                        foreach ($details as $d): ?>
                        <div class="profile-detail-row">
                            <i class="bi <?= $d['icon'] ?> text-muted me-2"></i>
                            <span class="text-muted small"><?= $d['label'] ?>:</span>
                            <span class="ms-auto small"><?= $d['value'] ? e($d['value']) : '<span class="text-muted">—</span>' ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: Edit form -->
                <div class="col-xl-8 col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profile Information
                        </div>
                        <div class="card-body">
                            <form method="POST" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">First Name</label>
                                        <input type="text" class="form-control" name="first_name"
                                               value="<?= e($profile['first_name'] ?? '') ?>"
                                               placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Last Name</label>
                                        <input type="text" class="form-control" name="last_name"
                                               value="<?= e($profile['last_name'] ?? '') ?>"
                                               placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Email Address</label>
                                        <input type="email" class="form-control bg-light"
                                               value="<?= e($profile['email'] ?? '') ?>" disabled>
                                        <div class="form-text">Email cannot be changed.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Phone Number</label>
                                        <input type="tel" class="form-control" name="phone"
                                               value="<?= e($profile['phone'] ?? '') ?>"
                                               placeholder="+968 XXXX XXXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Department</label>
                                        <input type="text" class="form-control" name="department"
                                               value="<?= e($profile['department'] ?? '') ?>"
                                               placeholder="e.g. Computer Science">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Nationality</label>
                                        <input type="text" class="form-control" name="nationality"
                                               value="<?= e($profile['nationality'] ?? '') ?>"
                                               placeholder="e.g. Omani">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">LinkedIn Profile URL</label>
                                        <input type="url" class="form-control" name="linkedin"
                                               value="<?= e($profile['linkedin'] ?? '') ?>"
                                               placeholder="https://linkedin.com/in/yourprofile">
                                    </div>
                                </div>

                                <div class="mt-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-portal">
                                        <i class="bi bi-check-lg me-1"></i>Save Changes
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/scripts.php'; ?>
</body>
</html>
