<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle   = document.getElementById('header-toggle');
    const nav      = document.getElementById('nav-bar');
    const body     = document.getElementById('body-pd');
    const header   = document.getElementById('header');
    const backdrop = document.getElementById('nav-backdrop');

    function isMobile() { return window.innerWidth < 768; }

    function openNav() {
        nav.classList.add('show');
        if (isMobile()) {
            backdrop && backdrop.classList.add('show');
        } else {
            body   && body.classList.add('body-pd');
            header && header.classList.add('body-pd');
        }
    }

    function closeNav() {
        nav.classList.remove('show');
        backdrop && backdrop.classList.remove('show');
        body   && body.classList.remove('body-pd');
        header && header.classList.remove('body-pd');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            nav.classList.contains('show') ? closeNav() : openNav();
        });
    }

    backdrop && backdrop.addEventListener('click', closeNav);

    // Close mobile nav on link click
    document.querySelectorAll('.nav_link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) closeNav();
        });
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) backdrop && backdrop.classList.remove('show');
    });

    // ── Add ripple to buttons ──────────────────────
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `
                position:absolute;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;
                width:${size}px;height:${size}px;border-radius:50%;
                background:rgba(255,255,255,0.15);transform:scale(0);
                animation:ripple 0.4s ease-out;pointer-events:none;
            `;
            btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);
            ripple.addEventListener('animationend', () => ripple.remove());
        });
    });
});

// Ripple keyframe
(function() {
    if (!document.getElementById('ripple-style')) {
        const s = document.createElement('style');
        s.id = 'ripple-style';
        s.textContent = '@keyframes ripple{to{transform:scale(2.5);opacity:0}}';
        document.head.appendChild(s);
    }
})();

// ── SweetAlert2 helper ───────────────────────────
function displayAlert(title, position, icon) {
    Swal.fire({
        position: position || 'center',
        icon: icon || 'info',
        title: title,
        showConfirmButton: false,
        timer: 2000,
        background: '#0D1B30',
        color: '#F1F5F9',
        iconColor: icon === 'success' ? '#22C55E' : icon === 'error' ? '#EF4444' : '#F59E0B',
        customClass: { popup: 'swal-dark' },
        toast: position === 'top-end',
        timerProgressBar: true
    });
}
</script>
