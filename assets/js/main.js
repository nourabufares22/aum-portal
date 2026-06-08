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

/* ── Particle network background ────────────────────────────── */
(function () {
    var canvas = document.createElement('canvas');
    canvas.id  = 'aum-particles';
    document.body.prepend(canvas);
    var ctx = canvas.getContext('2d');

    function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize);

    var pts = Array.from({ length: 55 }, function () {
        return {
            x:  Math.random() * canvas.width,
            y:  Math.random() * canvas.height,
            vx: (Math.random() - .5) * .45,
            vy: (Math.random() - .5) * .45,
            r:  Math.random() * 1.8 + .8
        };
    });

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (var i = 0; i < pts.length; i++) {
            for (var j = i + 1; j < pts.length; j++) {
                var dx = pts[i].x - pts[j].x, dy = pts[i].y - pts[j].y;
                var d  = Math.sqrt(dx * dx + dy * dy);
                if (d < 130) {
                    ctx.globalAlpha = (1 - d / 130) * .3;
                    ctx.strokeStyle = '#970000';
                    ctx.lineWidth   = .8;
                    ctx.beginPath();
                    ctx.moveTo(pts[i].x, pts[i].y);
                    ctx.lineTo(pts[j].x, pts[j].y);
                    ctx.stroke();
                }
            }
        }
        ctx.globalAlpha = 1;
        pts.forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(151,0,0,.45)';
            ctx.fill();
            p.x += p.vx; p.y += p.vy;
            if (p.x < 0 || p.x > canvas.width)  p.vx = -p.vx;
            if (p.y < 0 || p.y > canvas.height)  p.vy = -p.vy;
        });
        requestAnimationFrame(draw);
    }
    draw();
})();

/* ── Scroll reveal ───────────────────────────────────────────── */
(function () {
    if (!window.IntersectionObserver) return;
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('scroll-visible'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.07 });
    document.querySelectorAll(
        '.card,.stat-card,.job-card,.exp-card,.doc-card,.skill-tag,.app-summary-card,.quick-link-card'
    ).forEach(function (el, i) {
        el.classList.add('scroll-hidden');
        el.style.transitionDelay = (i % 6) * 0.07 + 's';
        obs.observe(el);
    });
})();

/* ── Counter animation ───────────────────────────────────────── */
(function () {
    if (!window.IntersectionObserver) return;
    function run(el) {
        var target = parseInt(el.textContent, 10);
        if (isNaN(target) || target < 2) return;
        var t0 = null, dur = 1000;
        requestAnimationFrame(function step(ts) {
            if (!t0) t0 = ts;
            var prog = Math.min((ts - t0) / dur, 1);
            var ease = 1 - Math.pow(1 - prog, 3);
            el.textContent = Math.floor(ease * target);
            if (prog < 1) requestAnimationFrame(step);
            else el.textContent = target;
        });
    }
    var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); co.unobserve(e.target); } });
    }, { threshold: 0.5 });
    document.querySelectorAll('.stat-value,.app-count,.app-summary-count,.arb-value').forEach(function (el) { co.observe(el); });
})();

/* ── 3D tilt on stat cards ───────────────────────────────────── */
(function () {
    document.querySelectorAll('.stat-card').forEach(function (card) {
        var glare = document.createElement('div');
        glare.className = 'tilt-glare';
        card.appendChild(glare);

        card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width  - .5;
            var y = (e.clientY - r.top)  / r.height - .5;
            card.style.transform = 'perspective(600px) rotateY(' + (x * 22) + 'deg) rotateX(' + (-y * 22) + 'deg) translateY(-10px) scale(1.04)';
            card.style.transition = 'none';
            glare.style.background = 'radial-gradient(circle at ' + ((x + .5) * 100) + '% ' + ((y + .5) * 100) + '%, rgba(255,255,255,.32) 0%, transparent 65%)';
            glare.style.opacity = '1';
        });
        card.addEventListener('mouseleave', function () {
            card.style.transition = 'transform .4s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease';
            card.style.transform  = '';
            glare.style.opacity   = '0';
        });
    });
})();

/* ── Ripple on buttons ───────────────────────────────────────── */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-portal,.btn-primary');
        if (!btn) return;
        var r = btn.getBoundingClientRect(), sz = Math.max(r.width, r.height);
        var rp = document.createElement('span');
        rp.className = 'ripple';
        rp.style.cssText = 'width:' + sz + 'px;height:' + sz + 'px;left:' + (e.clientX - r.left - sz / 2) + 'px;top:' + (e.clientY - r.top - sz / 2) + 'px;';
        btn.appendChild(rp);
        setTimeout(function () { rp.remove(); }, 650);
    });
})();

/* ── Magnetic buttons ────────────────────────────────────────── */
(function () {
    document.querySelectorAll('.btn-portal,.btn-primary,.btn-outline-primary').forEach(function (btn) {
        btn.addEventListener('mousemove', function (e) {
            var r  = btn.getBoundingClientRect();
            var mx = (e.clientX - r.left - r.width  / 2) * .28;
            var my = (e.clientY - r.top  - r.height / 2) * .28;
            btn.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
        });
        btn.addEventListener('mouseleave', function () {
            btn.style.transform = '';
            btn.style.transition = 'transform .4s cubic-bezier(.34,1.56,.64,1)';
        });
    });
})();

/* ── Typewriter on welcome banner ────────────────────────────── */
(function () {
    var el = document.querySelector('.welcome-banner h4');
    if (!el) return;
    var txt = el.textContent.trim();
    el.textContent = '';
    var cur = document.createElement('span');
    cur.className = 'typewriter-cursor';
    el.appendChild(cur);
    var i = 0;
    (function type() {
        if (i < txt.length) { el.insertBefore(document.createTextNode(txt[i++]), cur); setTimeout(type, 42); }
    })();
})();

/* ── Glitch: inject data-text attr on sidebar brand ─────────── */
(function () {
    var logo = document.querySelector('.sidebar-brand-logo');
    if (logo) logo.setAttribute('data-text', logo.textContent.trim());
})();

/* ── Auth floating orbs ──────────────────────────────────────── */
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

/* ── Stagger grid columns on load ────────────────────────────── */
(function () {
    document.querySelectorAll('.content-area > .row > [class*="col-"]').forEach(function (col, i) {
        col.style.animation      = 'fadeInUp .5s cubic-bezier(.34,1.2,.64,1) both';
        col.style.animationDelay = (i * 0.08) + 's';
    });
})();
