<?php
// ── Database Configuration ────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aum_portal');

// ── Project URL base — space encoded for HTTP Location headers ─
// Change 'aum%20portal' if you rename or move the folder.
define('BASE_URL', '/aum%20portal');

// ── Upload settings ───────────────────────────────────────────
define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
]);

// ── PDO Connection ────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    die('
    <!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8"><title>Database Error — AUM Portal</title>
    <style>
        body{font-family:sans-serif;background:#f4f6f9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .box{background:#fff;border-radius:12px;padding:40px;max-width:500px;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center}
        h2{color:#970000}p{color:#555}code{background:#f0f0f0;padding:4px 8px;border-radius:4px;font-size:.9em}
    </style></head><body>
    <div class="box">
        <h2>&#9888; Database Connection Error</h2>
        <p>Could not connect to MySQL. Please ensure XAMPP MySQL is running and the <code>aum_portal</code> database exists.</p>
        <p><strong>Steps:</strong><br>1. Start XAMPP MySQL<br>2. Open phpMyAdmin<br>3. Import <code>sql/schema.sql</code></p>
        <p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>
    </div></body></html>');
}
