<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name       = trim($_POST['first_name']       ?? '');
    $last_name        = trim($_POST['last_name']        ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         =      $_POST['password']         ?? '';
    $confirm_password =      $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name))   $errors[] = 'First name is required.';
    if (empty($last_name))    $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, "staff")');
            $stmt->execute([$email, $hashed]);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO staff_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $first_name, $last_name]);

            $pdo->commit();
            setFlash('success', 'Account created successfully! Please sign in.');
            header('Location: login.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AUM E-Portal</title>
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
            <h1>Join AUM Portal</h1>
            <p>Create your academic staff account and take the next step in your career.</p>
            <ul class="auth-features">
                <li><i class="bi bi-check-circle-fill"></i> Free to join for AUM staff</li>
                <li><i class="bi bi-check-circle-fill"></i> Showcase your qualifications</li>
                <li><i class="bi bi-check-circle-fill"></i> Apply to internal positions</li>
                <li><i class="bi bi-check-circle-fill"></i> Manage your academic CV</li>
            </ul>
        </div>
    </div>

    <!-- Right panel -->
    <div class="auth-panel-right">
        <div class="auth-form-container auth-form-wide">

            <div class="auth-logo-mobile d-lg-none mb-4">AUM</div>
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join the AUM Academic Staff E-Portal</p>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   placeholder="First Name" required
                                   value="<?= e($_POST['first_name'] ?? '') ?>">
                            <label for="first_name">First Name *</label>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   placeholder="Last Name" required
                                   value="<?= e($_POST['last_name'] ?? '') ?>">
                            <label for="last_name">Last Name *</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Email" required autocomplete="email"
                           value="<?= e($_POST['email'] ?? '') ?>">
                    <label for="email">Email Address *</label>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Password" required minlength="8">
                            <label for="password">Password * (min 8 chars)</label>
                            <button type="button" class="pw-toggle" onclick="togglePw('password','pw-eye1')">
                                <i class="bi bi-eye" id="pw-eye1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Confirm Password" required>
                            <label for="confirm_password">Confirm Password *</label>
                            <button type="button" class="pw-toggle" onclick="togglePw('confirm_password','pw-eye2')">
                                <i class="bi bi-eye" id="pw-eye2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg btn-portal mb-3">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <p class="text-center text-muted mb-0">
                Already have an account?
                <a href="login.php" class="auth-link">Sign in here</a>
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
