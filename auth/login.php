<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('SELECT id, email, password, role FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            // Route admin to admin panel, staff to staff portal
            $dest = $user['role'] === 'admin'
                ? BASE_URL . '/admin/dashboard.php'
                : BASE_URL . '/staff/dashboard.php';
            header('Location: ' . $dest);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AUM E-Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <!-- Left panel -->
    <div class="auth-panel-left d-none d-lg-flex">
        <div class="auth-panel-content">
            <div class="auth-panel-logo">AUM</div>
            <h1>American University of Madaba</h1>
            <p>E-Portal for Employment - Academic Staff</p>
            <ul class="auth-features">
                <li><i class="bi bi-check-circle-fill"></i> Manage your academic profile</li>
                <li><i class="bi bi-check-circle-fill"></i> Track publications & qualifications</li>
                <li><i class="bi bi-check-circle-fill"></i> Apply to job opportunities</li>
                <li><i class="bi bi-check-circle-fill"></i> Monitor application status</li>
            </ul>
        </div>
    </div>

    <!-- Right panel (form) -->
    <div class="auth-panel-right">
        <div class="auth-form-container">

            <div class="auth-logo-mobile d-lg-none mb-4">AUM</div>

            <h2 class="auth-title">Welcome back</h2>
            <p class="auth-subtitle">Sign in to your academic staff portal</p>

            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Email" required autocomplete="email"
                           value="<?= e($_POST['email'] ?? '') ?>">
                    <label for="email"><i class="bi bi-envelope me-1"></i>Email Address</label>
                </div>

                <div class="form-floating mb-4 position-relative">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Password" required autocomplete="current-password">
                    <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
                    <button type="button" class="pw-toggle" onclick="togglePw('password','pw-eye')">
                        <i class="bi bi-eye" id="pw-eye"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg btn-portal mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <p class="text-center text-muted mb-0">
                Don't have an account?
                <a href="register.php" class="auth-link">Register here</a>
            </p>

        </div>

        <footer class="auth-footer-text">
            &copy; <?= date('Y') ?> American University of Madaba. All rights reserved.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
