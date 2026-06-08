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

/* ── Scroll-reveal via IntersectionObserver ─────────────────── */
(function () {
    if (!window.IntersectionObserver) return;
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('scroll-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll(
        '.card, .stat-card, .job-card, .exp-card, .doc-card, .skill-tag, .app-summary-card, .quick-link-card'
    ).forEach(function (el, i) {
        el.classList.add('scroll-hidden');
        el.style.transitionDelay = (i % 6) * 0.07 + 's';
        observer.observe(el);
    });
})();

/* ── Counter animation for stat numbers ─────────────────────── */
(function () {
    if (!window.IntersectionObserver) return;
    function animateCount(el) {
        var target = parseInt(el.textContent, 10);
        if (isNaN(target) || target < 2) return;
        var start    = 0;
        var duration = 900;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var ease     = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.floor(ease * target);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        }
        requestAnimationFrame(step);
    }
    var counter = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                counter.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-value, .app-count, .app-summary-count, .arb-value').forEach(function (el) {
        counter.observe(el);
    });
})();

/* ── Ripple effect on buttons ───────────────────────────────── */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-portal, .btn-primary');
        if (!btn) return;
        var rect   = btn.getBoundingClientRect();
        var size   = Math.max(rect.width, rect.height);
        var ripple = document.createElement('span');
        ripple.className  = 'ripple';
        ripple.style.cssText = 'width:' + size + 'px;height:' + size + 'px;'
            + 'left:' + (e.clientX - rect.left - size / 2) + 'px;'
            + 'top:'  + (e.clientY - rect.top  - size / 2) + 'px;';
        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 600);
    });
})();

/* ── Floating orbs on auth panel ────────────────────────────── */
(function () {
    var panel = document.querySelector('.auth-panel-left');
    if (!panel) return;
    panel.style.position = 'relative';
    panel.style.overflow = 'hidden';
    [1, 2, 3].forEach(function (n) {
        var orb = document.createElement('div');
        orb.className = 'auth-orb auth-orb-' + n;
        panel.appendChild(orb);
    });
})();

/* ── Stagger-animate top-level grid columns on load ─────────── */
(function () {
    document.querySelectorAll('.content-area > .row > [class*="col-"]').forEach(function (col, i) {
        col.style.animation      = 'fadeInUp .45s ease both';
        col.style.animationDelay = (i * 0.09) + 's';
    });
})();
