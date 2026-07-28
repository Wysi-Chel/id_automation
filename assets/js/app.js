document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (!toggle || !sidebar) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('click', (event) => {
        if (
            window.matchMedia('(max-width: 760px)').matches
            && sidebar.classList.contains('is-open')
            && !sidebar.contains(event.target)
            && !toggle.contains(event.target)
        ) {
            closeSidebar();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
});
