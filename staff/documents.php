<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Documents';
$userId    = getCurrentUserId();

// ── Handle upload ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'upload') {
        $docType = $_POST['document_type'] ?? '';
        $allowed_types = ['cv','certificate','recommendation','supporting'];

        if (!in_array($docType, $allowed_types)) {
            setFlash('error', 'Invalid document type.');
        } elseif (empty($_FILES['document']['name'])) {
            setFlash('error', 'Please select a file to upload.');
        } else {
            $file      = $_FILES['document'];
            $origName  = $file['name'];
            $tmpPath   = $file['tmp_name'];
            $fileSize  = $file['size'];
            $uploadErr = $file['error'];

            if ($uploadErr !== UPLOAD_ERR_OK) {
                setFlash('error', 'Upload error. Please try again.');
            } elseif ($fileSize > MAX_FILE_SIZE) {
                setFlash('error', 'File exceeds maximum size of 10 MB.');
            } else {
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    setFlash('error', 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG.');
                } else {
                    // Verify MIME by reading file header
                    $finfo   = new finfo(FILEINFO_MIME_TYPE);
                    $mime    = $finfo->file($tmpPath);
                    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
                        setFlash('error', 'File content does not match its extension.');
                    } else {
                        // Build user-specific upload directory
                        $userDir = UPLOAD_PATH . $userId . DIRECTORY_SEPARATOR;
                        if (!is_dir($userDir)) {
                            mkdir($userDir, 0755, true);
                        }

                        $safeName  = $userId . '_' . $docType . '_' . time() . '.' . $ext;
                        $destPath  = $userDir . $safeName;
                        $relPath   = 'uploads/' . $userId . '/' . $safeName;

                        if (move_uploaded_file($tmpPath, $destPath)) {
                            $stmt = $pdo->prepare('INSERT INTO documents
                                                   (user_id,document_type,original_name,file_name,file_path,file_size)
                                                   VALUES (?,?,?,?,?,?)');
                            $stmt->execute([$userId, $docType, $origName, $safeName, $relPath, $fileSize]);
                            setFlash('success', 'Document uploaded successfully.');
                        } else {
                            setFlash('error', 'Failed to save the file. Check folder permissions.');
                        }
                    }
                }
            }
        }

    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT file_path FROM documents WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        $doc = $stmt->fetch();

        if ($doc) {
            $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $doc['file_path'];
            if (file_exists($fullPath)) @unlink($fullPath);

            $stmt = $pdo->prepare('DELETE FROM documents WHERE id=? AND user_id=?');
            $stmt->execute([$id, $userId]);
            setFlash('success', 'Document deleted.');
        }
    }

    header('Location: documents.php');
    exit;
}

// ── Fetch documents ───────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM documents WHERE user_id=? ORDER BY uploaded_at DESC');
$stmt->execute([$userId]);
$allDocs = $stmt->fetchAll();

$grouped = ['cv'=>[],'certificate'=>[],'recommendation'=>[],'supporting'=>[]];
foreach ($allDocs as $d) $grouped[$d['document_type']][] = $d;

$typeLabels = [
    'cv'             => 'CV / Resume',
    'certificate'    => 'Certificates',
    'recommendation' => 'Recommendation Letters',
    'supporting'     => 'Supporting Documents',
];
$typeIcons = [
    'cv'             => 'bi-file-person',
    'certificate'    => 'bi-patch-check',
    'recommendation' => 'bi-envelope-open',
    'supporting'     => 'bi-folder2',
];

function formatFileSize(int $bytes): string {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function fileIcon(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'          => 'bi-file-earmark-pdf text-danger',
        'doc','docx'   => 'bi-file-earmark-word text-primary',
        'jpg','jpeg','png' => 'bi-file-earmark-image text-success',
        default        => 'bi-file-earmark',
    };
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

            <div class="page-header mb-4">
                <div>
                    <h5 class="mb-1">Documents</h5>
                    <p class="text-muted mb-0">Upload and manage your CV, certificates, and other documents.</p>
                </div>
                <button class="btn btn-primary btn-portal" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-cloud-upload me-1"></i>Upload Document
                </button>
            </div>

            <!-- Upload info banner -->
            <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Accepted formats:</strong> PDF, DOC, DOCX, JPG, PNG &nbsp;|&nbsp;
                <strong>Max size:</strong> 10 MB per file
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Document sections -->
            <div class="row g-4">
                <?php foreach ($typeLabels as $type => $label):
                    $docs = $grouped[$type]; ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi <?= $typeIcons[$type] ?> me-2"></i><?= $label ?>
                                <span class="badge bg-white text-primary ms-2"><?= count($docs) ?></span>
                            </span>
                            <button class="btn btn-sm btn-outline-light"
                                    onclick="setDocType('<?= $type ?>')"
                                    data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="bi bi-plus-lg me-1"></i>Upload
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($docs)): ?>
                            <p class="text-muted mb-0 py-2">
                                <i class="bi bi-folder2-open me-1"></i>No <?= strtolower($label) ?> uploaded yet.
                            </p>
                            <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($docs as $doc): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="doc-card">
                                        <div class="doc-icon">
                                            <i class="bi <?= fileIcon($doc['file_name']) ?> fs-2"></i>
                                        </div>
                                        <div class="doc-info">
                                            <p class="doc-name" title="<?= e($doc['original_name']) ?>">
                                                <?= e($doc['original_name']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= formatFileSize($doc['file_size']) ?> &bull;
                                                <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="doc-actions">
                                            <a href="../<?= e($doc['file_path']) ?>" target="_blank"
                                               class="btn btn-sm btn-outline-primary" title="View/Download">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDeleteDoc(<?= $doc['id'] ?>,'<?= e(addslashes($doc['original_name'])) ?>')"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Upload Document</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Document Type *</label>
                        <select class="form-select" name="document_type" id="modalDocType" required>
                            <option value="">— Select Type —</option>
                            <?php foreach ($typeLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Select File *</label>
                        <input type="file" class="form-control" name="document" id="fileInput"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="form-text">Max 10 MB — PDF, DOC, DOCX, JPG, PNG</div>
                    </div>
                    <!-- File preview -->
                    <div id="filePreview" class="d-none">
                        <div class="doc-card">
                            <div class="doc-icon"><i class="bi bi-file-earmark fs-2 text-primary"></i></div>
                            <div class="doc-info">
                                <p class="doc-name mb-0" id="previewName">filename.pdf</p>
                                <small class="text-muted" id="previewSize">—</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-portal">
                        <i class="bi bi-cloud-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete form -->
<form id="deleteForm" method="POST" class="d-none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php include '../includes/scripts.php'; ?>
<script>
function setDocType(type) {
    document.getElementById('modalDocType').value = type;
}
function confirmDeleteDoc(id, name) {
    if (confirm('Delete document "' + name + '"?\n\nThis cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
// File preview on select
document.getElementById('fileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) { document.getElementById('filePreview').classList.add('d-none'); return; }
    document.getElementById('previewName').textContent = file.name;
    document.getElementById('previewSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('filePreview').classList.remove('d-none');
});
</script>
</body>
</html>
