(function () {
    'use strict';

    const toggle = document.getElementById('menu_toggle');
    const toggleControl = document.querySelector('.menu_toggle_icon');
    const navigationLinks = document.querySelectorAll('[data-nav-link]');
    let activeLink = null;

    if (!toggle || !toggleControl) {
        return;
    }

    const syncOpenState = function () {
        const isOpen = toggle.checked;
        toggleControl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('menu-open', isOpen);
        if (isOpen && activeLink) {
            window.requestAnimationFrame(function () {
                activeLink.scrollIntoView({ block: 'nearest' });
            });
        }
    };

    const closeMenu = function () {
        if (!toggle.checked) {
            return;
        }
        toggle.checked = false;
        syncOpenState();
    };

    toggle.addEventListener('change', syncOpenState);

    toggleControl.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        event.preventDefault();
        toggle.checked = !toggle.checked;
        syncOpenState();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || !toggle.checked) {
            return;
        }
        closeMenu();
        toggleControl.focus();
    });

    const currentPage = new URL(window.location.href).searchParams.get('page');
    navigationLinks.forEach(function (link) {
        const linkPage = new URL(link.href, window.location.href).searchParams.get('page');
        if (currentPage && currentPage === linkPage) {
            link.setAttribute('aria-current', 'page');
            activeLink = link;
        }
    });

    syncOpenState();
}());
