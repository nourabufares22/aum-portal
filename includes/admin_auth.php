<?php
// ── Admin Authentication & CSRF helpers ───────────────────────
// Always loaded AFTER config/db.php and includes/auth.php

function requireAdmin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        // Logged in but not admin — send back to staff portal
        setFlash('error', 'Access denied. Admin privileges required.');
        header('Location: ' . BASE_URL . '/staff/dashboard.php');
        exit;
    }
}

function getAdminId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

// ── CSRF ─────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Emit a hidden CSRF field — call inside every POST form */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Abort with 403 when the token is missing or wrong */
function verifyCsrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (empty($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('<h3 style="font-family:sans-serif;color:#970000">Security Error</h3>
             <p>Invalid or expired form token. Please go back and resubmit.</p>');
    }
}

// ── Status helpers ────────────────────────────────────────────

const APP_STATUSES = ['pending','under_review','accepted','rejected'];

function statusBadge(string $status): string {
    $map = [
        'pending'      => ['bg-warning text-dark', 'bi-clock-history',    'Pending'],
        'under_review' => ['bg-info text-white',   'bi-search',           'Under Review'],
        'accepted'     => ['bg-success',           'bi-check-circle-fill','Accepted'],
        'rejected'     => ['bg-danger',            'bi-x-circle-fill',    'Rejected'],
    ];
    [$cls, $icon, $label] = $map[$status] ?? ['bg-secondary','bi-question','Unknown'];
    return "<span class=\"badge {$cls}\"><i class=\"bi {$icon} me-1\"></i>{$label}</span>";
}
