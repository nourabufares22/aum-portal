<?php
/**
 * Secure file download — admin only.
 * Never exposes real file paths; validates ownership via DB only.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/admin_auth.php';
requireAdmin();

$docId = (int)($_GET['id'] ?? 0);
if (!$docId) { http_response_code(400); exit('Bad request.'); }

// Fetch document record from DB (path comes from DB, never from user input)
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) { http_response_code(404); exit('File not found.'); }

// Resolve absolute path
$projectRoot = dirname(__DIR__); // admin/ → project root
$fullPath    = realpath($projectRoot . DIRECTORY_SEPARATOR . $doc['file_path']);

// Security: ensure resolved path is inside the uploads directory (prevent traversal)
$uploadsDir = realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads');
if ($fullPath === false || $uploadsDir === false) {
    http_response_code(404); exit('File not found on server.');
}
if (strncmp($fullPath, $uploadsDir, strlen($uploadsDir)) !== 0) {
    http_response_code(403); exit('Access denied.');
}
if (!is_file($fullPath)) {
    http_response_code(404); exit('File not found on server.');
}

// MIME mapping
$ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

// Safe filename for Content-Disposition
$safeFilename = preg_replace('/[^\w.\- ]/', '_', $doc['original_name']);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($safeFilename) . '"');
header('Content-Length: ' . filesize($fullPath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Pragma: no-cache');

readfile($fullPath);
exit;
