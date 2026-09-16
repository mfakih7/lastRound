document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('app-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openButton = document.getElementById('sidebar-open');
    const closeButton = document.getElementById('sidebar-close');
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');

    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        openButton?.setAttribute('aria-expanded', 'true');
    };

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        openButton?.setAttribute('aria-expanded', 'false');
    };

    openButton?.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-close-sidebar]').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 1023px)').matches) {
                closeSidebar();
            }
        });
    });

    const closeUserMenu = () => {
        userMenu?.classList.add('hidden');
        userMenuButton?.setAttribute('aria-expanded', 'false');
    };

    userMenuButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        const isHidden = userMenu?.classList.contains('hidden');
        closeAllActionMenus();
        userMenu?.classList.toggle('hidden');
        userMenuButton?.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });

    const closeAllActionMenus = () => {
        document.querySelectorAll('[data-menu-panel]').forEach((panel) => {
            panel.classList.add('hidden');
        });
        document.querySelectorAll('[data-menu-button]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
    };

    document.querySelectorAll('[data-menu]').forEach((root) => {
        const button = root.querySelector('[data-menu-button]');
        const panel = root.querySelector('[data-menu-panel]');

        button?.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = panel?.classList.contains('hidden');
            closeUserMenu();
            closeAllActionMenus();

            if (willOpen) {
                panel?.classList.remove('hidden');
                button.setAttribute('aria-expanded', 'true');
            }
        });

        panel?.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', () => {
        closeUserMenu();
        closeAllActionMenus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeUserMenu();
            closeAllActionMenus();
            closeSidebar();
        }
    });

    userMenu?.addEventListener('click', (event) => {
        event.stopPropagation();
    });
});
