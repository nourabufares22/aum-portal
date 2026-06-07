<?php
/**
 * AUM E-Portal — Entry point
 * Redirects to dashboard if logged in, otherwise to login.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/auth/login.php');
}
exit;
