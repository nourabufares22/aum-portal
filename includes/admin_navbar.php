<?php
$_adminId = getAdminId();
$_stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
$_stmt->execute([$_adminId]);
$_adminUser = $_stmt->fetch();
$_adminEmail = $_adminUser['email'] ?? 'Admin';
$_adminLetter = strtoupper(substr($_adminEmail, 0, 1));
?>
<nav class="top-navbar" id="topNavbar">
    <div class="navbar-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="page-title mb-0"><?= isset($pageTitle) ? e($pageTitle) : 'Admin Dashboard' ?></h5>
        <span class="admin-label-badge">Admin</span>
    </div>

    <div class="navbar-right">
        <div class="dropdown">
            <button class="user-dropdown-btn dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar admin-avatar"><?= e($_adminLetter) ?></div>
                <div class="user-meta d-none d-md-flex">
                    <span class="user-name"><?= e($_adminEmail) ?></span>
                    <small class="user-dept">Administrator</small>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li class="dropdown-header">
                    <strong>Administrator</strong><br>
                    <small class="text-muted"><?= e($_adminEmail) ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="../staff/dashboard.php">
                        <i class="bi bi-person me-2"></i>Staff Portal
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
