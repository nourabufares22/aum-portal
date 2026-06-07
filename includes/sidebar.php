<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    ['href'=>'dashboard.php',     'icon'=>'bi-speedometer2',    'label'=>'Dashboard'],
    ['href'=>'profile.php',       'icon'=>'bi-person-circle',   'label'=>'My Profile'],
    ['href'=>'qualifications.php','icon'=>'bi-award',           'label'=>'Qualifications'],
    ['href'=>'publications.php',  'icon'=>'bi-journal-text',    'label'=>'Publications'],
    ['href'=>'experience.php',    'icon'=>'bi-briefcase',       'label'=>'Experience'],
    ['href'=>'skills.php',        'icon'=>'bi-lightning-charge','label'=>'Skills'],
    ['href'=>'documents.php',     'icon'=>'bi-folder2-open',    'label'=>'Documents'],
    ['href'=>'jobs.php',          'icon'=>'bi-search',          'label'=>'Job Opportunities'],
    ['href'=>'applications.php',  'icon'=>'bi-clipboard-check', 'label'=>'My Applications'],
];
?>
<nav class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-logo">AUM</div>
        <div class="sidebar-brand-text">
            <span class="brand-title">Academic Portal</span>
            <span class="brand-sub">E-Portal for Employment</span>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <!-- Navigation -->
    <ul class="sidebar-nav list-unstyled mb-0">
        <?php foreach ($navItems as $item):
            $active = ($currentPage === basename($item['href'], '.php')); ?>
        <li>
            <a href="<?= $item['href'] ?>" class="sidebar-link <?= $active ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Spacer -->
    <div class="sidebar-spacer"></div>

    <div class="sidebar-divider"></div>

    <!-- Logout -->
    <a href="../auth/logout.php" class="sidebar-link sidebar-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>

</nav>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
