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
        toggle.classList.add('bx-x');
        if (isMobile()) {
            backdrop && backdrop.classList.add('show');
        } else {
            body   && body.classList.add('body-pd');
            header && header.classList.add('body-pd');
        }
    }

    function closeNav() {
        nav.classList.remove('show');
        toggle.classList.remove('bx-x');
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

    // Re-evaluate on resize (mobile -> desktop transition)
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            backdrop && backdrop.classList.remove('show');
        }
    });
});

function displayAlert(title, position, icon) {
    Swal.fire({
        position: position,
        icon: icon,
        title: title,
        showConfirmButton: false,
        timer: 1500,
        background: '#1E293B',
        color: '#F8FAFC'
    });
}
</script>
