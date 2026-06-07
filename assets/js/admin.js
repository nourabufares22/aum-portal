/* ============================================================
   AUM E-Portal — Admin JavaScript
   ============================================================ */

'use strict';

/* ── Confirm before quick-status form submit ─────────────────── */
(function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const msg = form.getAttribute('data-confirm');
            if (!confirm(msg)) e.preventDefault();
        });
    });
})();

/* ── Auto-dismiss success alerts after 4 s ──────────────────── */
(function () {
    document.querySelectorAll('.alert-success, .alert-info').forEach(function (el) {
        setTimeout(function () {
            const b = bootstrap.Alert.getOrCreateInstance(el);
            if (b) b.close();
        }, 4000);
    });
})();

/* ── Highlight current table row on hover ───────────────────── */
(function () {
    document.querySelectorAll('.admin-table tbody tr').forEach(function (tr) {
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', function (e) {
            // Only navigate if the click wasn't on a button/link
            if (e.target.closest('a, button')) return;
            const link = tr.querySelector('a[href]');
            if (link) window.location.href = link.href;
        });
    });
})();

/* ── Export CSV button feedback ─────────────────────────────── */
(function () {
    document.querySelectorAll('a[href*="export="]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Preparing…';
            setTimeout(function () { btn.innerHTML = orig; }, 3000);
        });
    });
})();
