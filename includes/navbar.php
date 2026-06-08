<?php
// Fetch display name for navbar
$_navUserId = getCurrentUserId();
$_navProfile = null;
if (isset($pdo)) {
    $_stmt = $pdo->prepare('SELECT sp.first_name, sp.last_name, sp.department, u.email
                             FROM users u
                             LEFT JOIN staff_profiles sp ON sp.user_id = u.id
                             WHERE u.id = ?');
    $_stmt->execute([$_navUserId]);
    $_navProfile = $_stmt->fetch();
}
$_displayName = trim(($_navProfile['first_name'] ?? '') . ' ' . ($_navProfile['last_name'] ?? ''));
if (empty(trim($_displayName))) $_displayName = $_navProfile['email'] ?? 'Staff Member';
$_deptLabel   = $_navProfile['department'] ?? 'Academic Staff';
$_avatarLetter = strtoupper(substr($_displayName, 0, 1)) ?: 'S';
?>
<nav class="top-navbar" id="topNavbar">

    <div class="navbar-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="page-title mb-0"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></h5>
    </div>

    <div class="navbar-right">
        <!-- Notification bell (cosmetic) -->
        <button class="navbar-icon-btn" type="button" title="Notifications">
            <i class="bi bi-bell"></i>
        </button>

        <!-- User dropdown -->
        <div class="dropdown">
            <button class="user-dropdown-btn dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar"><?= e($_avatarLetter) ?></div>
                <div class="user-meta d-none d-md-flex">
                    <span class="user-name"><?= e($_displayName) ?></span>
                    <small class="user-dept"><?= e($_deptLabel) ?></small>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li class="dropdown-header">
                    <strong><?= e($_displayName) ?></strong><br>
                    <small class="text-muted"><?= e($_navProfile['email'] ?? '') ?></small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="profile.php">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="documents.php">
                        <i class="bi bi-folder2-open me-2"></i>My Documents
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
