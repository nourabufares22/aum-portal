/* ============================================================
   AUM E-Portal — Main JavaScript
   ============================================================ */

'use strict';

/* ── Sidebar toggle ─────────────────────────────────────────── */
(function () {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('mainContent');

    if (!toggleBtn || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('show');
    }
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('show');
    }

    toggleBtn.addEventListener('click', function () {
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    // Close on overlay click (mobile)
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Desktop: toggle collapsed class instead
    function handleResize() {
        if (window.innerWidth >= 992) {
            closeSidebar(); // clear mobile state
        }
    }
    window.addEventListener('resize', handleResize);
})();

/* ── Password toggle ────────────────────────────────────────── */
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input) return;

    if (input.type === 'password') {
        input.type  = 'text';
        if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
    } else {
        input.type  = 'password';
        if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
    }
}

/* ── Auto-dismiss alerts after 5 s ──────────────────────────── */
(function () {
    const alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-info');
    alerts.forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
})();

/* ── Active sidebar highlight (hash-free fallback) ──────────── */
(function () {
    const links = document.querySelectorAll('.sidebar-link');
    const cur   = window.location.pathname.split('/').pop();
    links.forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && href === cur) {
            link.classList.add('active');
        }
    });
})();

/* ── Bootstrap tooltip init ─────────────────────────────────── */
(function () {
    const tips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tips.forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
})();

/* ── Generic CRUD helpers (exposed globally for inline calls) ── */

/**
 * Submit a hidden delete form by setting its ID and calling submit.
 * Used by qualifications, publications, experience pages.
 */
function confirmDelete(id, typeName, itemName) {
    if (confirm('Delete ' + typeName + ':\n"' + itemName + '"\n\nThis cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        const inp  = document.getElementById('deleteId');
        if (form && inp) {
            inp.value = id;
            form.submit();
        }
    }
}

/* ── File input size guard ───────────────────────────────────── */
(function () {
    const MAX = 10 * 1024 * 1024; // 10 MB
    document.querySelectorAll('input[type="file"]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            const file = this.files[0];
            if (file && file.size > MAX) {
                alert('File "' + file.name + '" exceeds the 10 MB size limit.\nPlease choose a smaller file.');
                this.value = '';
                const preview = document.getElementById('filePreview');
                if (preview) preview.classList.add('d-none');
            }
        });
    });
})();

/* ── Smooth card hover enhancement ──────────────────────────── */
(function () {
    document.querySelectorAll('.stat-card, .job-card, .exp-card').forEach(function (card) {
        card.style.transition = 'transform .2s ease, box-shadow .2s ease';
    });
})();
