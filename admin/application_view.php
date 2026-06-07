<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$appId = (int)($_GET['id'] ?? 0);
if (!$appId) { header('Location: applications.php'); exit; }

// ── Load application ──────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT a.*, j.title AS job_title, j.department AS job_dept,
             j.description AS job_desc, j.requirements AS job_req,
             j.deadline, u.email,
             CONCAT(COALESCE(sp.first_name,''), ' ', COALESCE(sp.last_name,'')) AS full_name
     FROM applications a
     JOIN users u        ON u.id = a.user_id
     JOIN jobs j         ON j.id = a.job_id
     LEFT JOIN staff_profiles sp ON sp.user_id = a.user_id
     WHERE a.id = ?"
);
$stmt->execute([$appId]);
$app = $stmt->fetch();
if (!$app) { setFlash('error', 'Application not found.'); header('Location: applications.php'); exit; }

$applicantId = $app['user_id'];
$pageTitle   = 'Application #' . $appId;

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['status']      ?? '';
        $note      = trim($_POST['note']   ?? '');

        if (!in_array($newStatus, APP_STATUSES)) {
            setFlash('error', 'Invalid status value.');
        } else {
            $upd = $pdo->prepare('UPDATE applications SET status=? WHERE id=?');
            $upd->execute([$newStatus, $appId]);

            if (!empty($note)) {
                $ins = $pdo->prepare('INSERT INTO admin_notes (application_id, admin_id, note) VALUES (?,?,?)');
                $ins->execute([$appId, getAdminId(), $note]);
            }

            setFlash('success', 'Application status updated to "' . ucwords(str_replace('_',' ',$newStatus)) . '".');
        }

    } elseif ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if (empty($note)) {
            setFlash('error', 'Note cannot be empty.');
        } else {
            $ins = $pdo->prepare('INSERT INTO admin_notes (application_id, admin_id, note) VALUES (?,?,?)');
            $ins->execute([$appId, getAdminId(), $note]);
            setFlash('success', 'Note added.');
        }

    } elseif ($action === 'quick_status') {
        // Accept / Reject / Review quick buttons
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, APP_STATUSES)) {
            $upd = $pdo->prepare('UPDATE applications SET status=? WHERE id=?');
            $upd->execute([$newStatus, $appId]);
            setFlash('success', 'Status updated to "' . ucwords(str_replace('_',' ',$newStatus)) . '".');
        }
    }

    header('Location: application_view.php?id=' . $appId);
    exit;
}

// ── Reload after any redirected action ───────────────────────
$stmt->execute([$appId]);
$app = $stmt->fetch();

// ── Applicant profile ─────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM staff_profiles WHERE user_id = ?');
$stmt->execute([$applicantId]);
$profile = $stmt->fetch() ?: [];

// ── Qualifications, Publications, Experience, Skills ──────────
$qual = $pdo->prepare('SELECT * FROM qualifications WHERE user_id=? ORDER BY graduation_year DESC');
$qual->execute([$applicantId]); $qualifications = $qual->fetchAll();

$pub = $pdo->prepare('SELECT * FROM publications WHERE user_id=? ORDER BY publication_year DESC');
$pub->execute([$applicantId]); $publications = $pub->fetchAll();

$exp = $pdo->prepare('SELECT * FROM experiences WHERE user_id=? ORDER BY years_of_experience DESC');
$exp->execute([$applicantId]); $experiences = $exp->fetchAll();

$sk = $pdo->prepare('SELECT * FROM skills WHERE user_id=? ORDER BY skill_name');
$sk->execute([$applicantId]); $skills = $sk->fetchAll();

// ── Documents ─────────────────────────────────────────────────
$docs = $pdo->prepare('SELECT * FROM documents WHERE user_id=? ORDER BY document_type, uploaded_at DESC');
$docs->execute([$applicantId]); $documents = $docs->fetchAll();
$cvDocs = array_filter($documents, fn($d) => $d['document_type'] === 'cv');

// ── Admin notes ───────────────────────────────────────────────
$notes = $pdo->prepare(
    "SELECT an.*, u.email AS admin_email
     FROM admin_notes an JOIN users u ON u.id = an.admin_id
     WHERE an.application_id = ? ORDER BY an.created_at DESC"
);
$notes->execute([$appId]); $adminNotes = $notes->fetchAll();
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

            <!-- Back nav -->
            <div class="mb-3 d-flex align-items-center gap-3">
                <a href="applications.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>All Applications
                </a>
                <span class="text-muted small">Application #<?= $appId ?></span>
                <div class="ms-auto">
                    <?= statusBadge($app['status']) ?>
                </div>
            </div>

            <div class="row g-4">

                <!-- ── Left: Applicant profile ── -->
                <div class="col-lg-8">

                    <!-- Applicant header -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-4">
                                <div class="app-big-avatar">
                                    <?= strtoupper(substr(trim($app['full_name']) ?: $app['email'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">
                                        <?= e(trim($app['full_name']) ?: 'Name not set') ?>
                                    </h5>
                                    <p class="text-muted mb-1">
                                        <i class="bi bi-envelope me-1"></i><?= e($app['email']) ?>
                                        <?php if (!empty($profile['phone'])): ?>
                                        &nbsp;&bull;&nbsp;
                                        <i class="bi bi-telephone me-1"></i><?= e($profile['phone']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <?php if (!empty($profile['department'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building me-1"></i><?= e($profile['department']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($profile['nationality'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-globe me-1"></i><?= e($profile['nationality']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($profile['linkedin'])): ?>
                                        <a href="<?= e($profile['linkedin']) ?>" target="_blank"
                                           class="badge bg-primary text-white text-decoration-none">
                                            <i class="bi bi-linkedin me-1"></i>LinkedIn
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- CV download shortcut -->
                                <?php foreach ($cvDocs as $cv): ?>
                                <a href="download.php?id=<?= $cv['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i>Download CV
                                </a>
                                <?php break; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Profile tabs -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body pb-0">
                            <ul class="nav nav-tabs" id="profileTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-qual">
                                        Qualifications <span class="badge bg-secondary ms-1"><?= count($qualifications) ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-pub">
                                        Publications <span class="badge bg-secondary ms-1"><?= count($publications) ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-exp">
                                        Experience <span class="badge bg-secondary ms-1"><?= count($experiences) ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-skills">
                                        Skills <span class="badge bg-secondary ms-1"><?= count($skills) ?></span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#tab-docs">
                                        Documents <span class="badge bg-secondary ms-1"><?= count($documents) ?></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="tab-content p-3">

                            <!-- Qualifications -->
                            <div class="tab-pane fade show active" id="tab-qual">
                                <?php if (empty($qualifications)): ?>
                                <p class="text-muted py-2">No qualifications on record.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr><th>Degree</th><th>University</th><th>Year</th><th>Certification</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($qualifications as $q): ?>
                                            <tr>
                                                <td class="fw-medium"><?= e($q['degree']) ?></td>
                                                <td><?= e($q['university']) ?></td>
                                                <td><span class="badge bg-primary-subtle text-primary"><?= e($q['graduation_year']) ?></span></td>
                                                <td><?= $q['certification'] ? e($q['certification']) : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Publications -->
                            <div class="tab-pane fade" id="tab-pub">
                                <?php if (empty($publications)): ?>
                                <p class="text-muted py-2">No publications on record.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr><th>Title</th><th>Journal</th><th>Year</th><th>Research Area</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($publications as $p): ?>
                                            <tr>
                                                <td class="fw-medium"><?= e($p['title']) ?></td>
                                                <td><?= e($p['journal']) ?></td>
                                                <td><span class="badge bg-success-subtle text-success"><?= e($p['publication_year']) ?></span></td>
                                                <td><?= $p['research_interest'] ? e($p['research_interest']) : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Experience -->
                            <div class="tab-pane fade" id="tab-exp">
                                <?php if (empty($experiences)): ?>
                                <p class="text-muted py-2">No experience on record.</p>
                                <?php else: ?>
                                <div class="row g-3">
                                <?php foreach ($experiences as $ex): ?>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-3">
                                            <p class="fw-semibold mb-0"><?= e($ex['position']) ?></p>
                                            <p class="text-muted small mb-1"><?= e($ex['institution']) ?></p>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <?= $ex['years_of_experience'] ?> yr<?= $ex['years_of_experience'] != 1 ? 's' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Skills -->
                            <div class="tab-pane fade" id="tab-skills">
                                <?php if (empty($skills)): ?>
                                <p class="text-muted py-2">No skills on record.</p>
                                <?php else: ?>
                                <div class="skills-cloud mt-2">
                                    <?php foreach ($skills as $sk): ?>
                                    <span class="skill-tag" style="cursor:default">
                                        <?= e($sk['skill_name']) ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Documents -->
                            <div class="tab-pane fade" id="tab-docs">
                                <?php if (empty($documents)): ?>
                                <p class="text-muted py-2">No documents uploaded.</p>
                                <?php else: ?>
                                <?php
                                $docTypeLabels = [
                                    'cv'=>'CV / Resume','certificate'=>'Certificates',
                                    'recommendation'=>'Recommendation Letters','supporting'=>'Supporting'
                                ];
                                $docsByType = [];
                                foreach ($documents as $d) $docsByType[$d['document_type']][] = $d;
                                foreach ($docsByType as $type => $typeDocs): ?>
                                <h6 class="text-muted small text-uppercase mt-3 mb-2">
                                    <?= $docTypeLabels[$type] ?? $type ?>
                                </h6>
                                <?php foreach ($typeDocs as $doc): ?>
                                <div class="doc-card mb-2">
                                    <div class="doc-icon">
                                        <?php
                                        $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                        $ico = match($ext) {
                                            'pdf'  => 'bi-file-earmark-pdf text-danger',
                                            'doc','docx' => 'bi-file-earmark-word text-primary',
                                            default => 'bi-file-earmark-image text-success',
                                        };
                                        ?>
                                        <i class="bi <?= $ico ?> fs-3"></i>
                                    </div>
                                    <div class="doc-info">
                                        <p class="doc-name"><?= e($doc['original_name']) ?></p>
                                        <small class="text-muted">
                                            <?= round($doc['file_size']/1024, 1) ?> KB &bull;
                                            <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="doc-actions">
                                        <a href="download.php?id=<?= $doc['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; endforeach; ?>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div><!-- /.col-lg-8 -->

                <!-- ── Right: Actions ── -->
                <div class="col-lg-4">

                    <!-- Job info -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header-custom">
                            <i class="bi bi-briefcase me-2"></i>Applied Position
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-1"><?= e($app['job_title']) ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-building me-1"></i><?= e($app['job_dept'] ?? 'AUM') ?>
                            </p>
                            <?php if ($app['deadline']): ?>
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                Deadline: <?= date('M j, Y', strtotime($app['deadline'])) ?>
                            </small>
                            <?php endif; ?>
                            <?php if (!empty($app['cover_letter'])): ?>
                            <div class="mt-3 pt-3 border-top">
                                <p class="text-muted small fw-medium mb-1">Cover Letter:</p>
                                <p class="text-muted small mb-0 fst-italic">
                                    <?= e(mb_strimwidth($app['cover_letter'], 0, 300, '…')) ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Applied: <?= date('M j, Y g:i A', strtotime($app['applied_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header-custom">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                Current status: <?= statusBadge($app['status']) ?>
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <?php
                                $quickActions = [
                                    ['accepted',     'btn-success',      'bi-check-circle',  'Accept Application'],
                                    ['under_review',  'btn-info',         'bi-search',        'Mark Under Review'],
                                    ['rejected',     'btn-danger',       'bi-x-circle',      'Reject Application'],
                                    ['pending',      'btn-warning',      'bi-clock',         'Reset to Pending'],
                                ];
                                foreach ($quickActions as [$s, $cls, $ico, $lbl]):
                                    if ($s === $app['status']) continue; ?>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"  value="quick_status">
                                    <input type="hidden" name="status"  value="<?= $s ?>">
                                    <button type="submit" class="btn <?= $cls ?> btn-sm w-100 text-start">
                                        <i class="bi <?= $ico ?> me-2"></i><?= $lbl ?>
                                    </button>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Status + note form -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header-custom">
                            <i class="bi bi-pencil-square me-2"></i>Update Status & Add Note
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_status">
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">New Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach (APP_STATUSES as $s): ?>
                                        <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>>
                                            <?= ucwords(str_replace('_',' ',$s)) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-medium">
                                        Admin Note <span class="text-muted">(optional)</span>
                                    </label>
                                    <textarea name="note" class="form-control form-control-sm" rows="3"
                                              placeholder="Add an internal note about this decision…"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-portal btn-sm w-100">
                                    <i class="bi bi-check-lg me-1"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Admin notes history -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-journal-text me-2"></i>Admin Notes</span>
                            <span class="badge bg-white text-primary"><?= count($adminNotes) ?></span>
                        </div>
                        <div class="card-body" style="max-height:320px;overflow-y:auto">
                            <?php if (empty($adminNotes)): ?>
                            <p class="text-muted small text-center py-2">No notes yet.</p>
                            <?php else: ?>
                            <?php foreach ($adminNotes as $note): ?>
                            <div class="admin-note-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="fw-medium"><?= e($note['admin_email']) ?></small>
                                    <small class="text-muted"><?= date('M j, g:i A', strtotime($note['created_at'])) ?></small>
                                </div>
                                <p class="text-muted small mb-0"><?= nl2br(e($note['note'])) ?></p>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <!-- Add standalone note -->
                        <div class="card-footer bg-transparent">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_note">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="note"
                                           placeholder="Add a quick note…" required>
                                    <button type="submit" class="btn btn-primary btn-portal">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div><!-- /.col-lg-4 -->
            </div><!-- /.row -->
        </div>
    </div>
</div>
<?php include '../includes/admin_scripts.php'; ?>
</body>
</html>
