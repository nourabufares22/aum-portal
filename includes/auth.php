<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function getCurrentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $map = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    $cls = $map[$flash['type']] ?? 'info';
    echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">'
       . '<i class="bi bi-' . ($cls === 'success' ? 'check-circle' : 'exclamation-triangle') . ' me-2"></i>'
       . htmlspecialchars($flash['message'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ── Profile completion (7 optional fields) ────────────────────
function profileCompletion(array $p): int {
    $fields = ['first_name','last_name','phone','department','nationality','linkedin'];
    $filled = array_reduce($fields, fn($c, $f) => $c + (!empty($p[$f]) ? 1 : 0), 0);
    return (int)(($filled / count($fields)) * 100);
}

// ── Sanitise output ───────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
