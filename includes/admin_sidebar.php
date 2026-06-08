<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Pending count badge
$_pendingCount = 0;
if (isset($pdo)) {
    $_pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
}

$adminNav = [
    ['href'=>'dashboard.php',    'icon'=>'bi-speedometer2',     'label'=>'Dashboard'],
    ['href'=>'applications.php', 'icon'=>'bi-clipboard-data',   'label'=>'Applications',
     'badge'=>$_pendingCount > 0 ? $_pendingCount : null],
    ['href'=>'jobs.php',         'icon'=>'bi-briefcase',        'label'=>'Job Management'],
    ['href'=>'reports.php',      'icon'=>'bi-bar-chart-line',   'label'=>'Reports & Analytics'],
];
?>
<nav class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">AUM</div>
        <div class="sidebar-brand-text">
            <span class="brand-title">Admin Panel</span>
            <span class="brand-sub">E-Portal for Employment</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <ul class="sidebar-nav list-unstyled mb-0">
        <?php foreach ($adminNav as $item):
            $active = ($currentPage === basename($item['href'], '.php')); ?>
        <li>
            <a href="<?= $item['href'] ?>" class="sidebar-link <?= $active ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
                <?php if (!empty($item['badge'])): ?>
                <span class="sidebar-badge"><?= $item['badge'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-spacer"></div>
    <div class="sidebar-divider"></div>

    <a href="../auth/logout.php" class="sidebar-link sidebar-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>

</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
