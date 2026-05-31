(() => {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    void prefersReducedMotion;

    function initSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const toggles = document.querySelectorAll('[data-sidebar-toggle]');
        const closers = document.querySelectorAll('[data-sidebar-close]');
        const backdrop = document.querySelector('.app-sidebar-backdrop');

        if (!sidebar) {
            return;
        }

        const openSidebar = () => {
            sidebar.classList.add('is-open');
            backdrop?.classList.add('is-open');
            document.body.classList.add('sidebar-open');
        };

        const closeSidebar = () => {
            sidebar.classList.remove('is-open');
            backdrop?.classList.remove('is-open');
            document.body.classList.remove('sidebar-open');
        };

        toggles.forEach((button) => {
            button.addEventListener('click', openSidebar);
        });

        closers.forEach((button) => {
            button.addEventListener('click', closeSidebar);
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991) {
                closeSidebar();
            }
        });
    }

    function initProfileDropdown() {
        const profile = document.querySelector('[data-profile-menu]');
        const toggle = document.querySelector('[data-profile-toggle]');

        if (!profile || !toggle) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            profile.classList.toggle('is-open');
        });

        document.addEventListener('click', (event) => {
            if (!profile.contains(event.target)) {
                profile.classList.remove('is-open');
            }
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                profile.classList.remove('is-open');
            }
        });
    }

    function initActiveMenuFallback() {
        const links = document.querySelectorAll('.app-sidebar-link[href]');
        const currentPath = window.location.pathname.replace(/\/+$/, '');

        if (!links.length) {
            return;
        }

        const hasActive = Array.from(links).some((link) => link.classList.contains('is-active'));

        if (hasActive) {
            return;
        }

        links.forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            const linkPath = url.pathname.replace(/\/+$/, '');

            if (currentPath === linkPath || currentPath.startsWith(`${linkPath}/`)) {
                link.classList.add('is-active');
            }
        });
    }

    function initGlobalSearch() {
        const input = document.querySelector('[data-global-search]');
        const wrap = document.querySelector('[data-global-search-wrap]');
        const results = document.querySelector('[data-global-search-results]');

        if (!input || !wrap || !results) {
            return;
        }

        const menuItems = Array.from(document.querySelectorAll('.app-sidebar-link[href]'))
            .map((link) => ({
                label: link.textContent.trim().replace(/\s+/g, ' '),
                href: link.href,
            }))
            .filter((item) => item.label !== '');

        let matches = [];
        let activeIndex = -1;

        const closeResults = () => {
            results.hidden = true;
            results.innerHTML = '';
            matches = [];
            activeIndex = -1;
            wrap.classList.remove('is-open');
        };

        const highlight = () => {
            const options = results.querySelectorAll('[data-search-option]');
            options.forEach((option, index) => {
                option.classList.toggle('is-active', index === activeIndex);
            });
        };

        const goTo = (index) => {
            const item = matches[index];
            if (item) {
                window.location.href = item.href;
            }
        };

        const render = () => {
            const keyword = input.value.trim().toLowerCase();

            if (!keyword) {
                closeResults();
                return;
            }

            matches = menuItems.filter((item) => item.label.toLowerCase().includes(keyword));
            activeIndex = matches.length ? 0 : -1;

            if (!matches.length) {
                results.innerHTML = '<div class="app-search-empty">Menu tidak ditemukan</div>';
                results.hidden = false;
                wrap.classList.add('is-open');
                return;
            }

            results.innerHTML = matches
                .map((item, index) => `
                    <button type="button" class="app-search-option" data-search-option data-index="${index}">
                        <i class="ti ti-arrow-right"></i>
                        <span></span>
                    </button>
                `)
                .join('');

            // Set text via textContent to avoid HTML injection from menu labels
            results.querySelectorAll('[data-search-option]').forEach((option, index) => {
                option.querySelector('span').textContent = matches[index].label;
                option.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    goTo(index);
                });
                option.addEventListener('mouseenter', () => {
                    activeIndex = index;
                    highlight();
                });
            });

            results.hidden = false;
            wrap.classList.add('is-open');
            highlight();
        };

        input.addEventListener('input', render);
        input.addEventListener('focus', render);

        input.addEventListener('keydown', (event) => {
            if (results.hidden || !matches.length) {
                if (event.key === 'Escape') {
                    input.blur();
                }
                return;
            }

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    activeIndex = (activeIndex + 1) % matches.length;
                    highlight();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    activeIndex = (activeIndex - 1 + matches.length) % matches.length;
                    highlight();
                    break;
                case 'Enter':
                    event.preventDefault();
                    goTo(activeIndex >= 0 ? activeIndex : 0);
                    break;
                case 'Escape':
                    closeResults();
                    input.blur();
                    break;
                default:
                    break;
            }
        });

        document.addEventListener('click', (event) => {
            if (!wrap.contains(event.target)) {
                closeResults();
            }
        });
    }

    function initThemeButton() {
        const button = document.querySelector('[data-theme-toggle]');

        if (!button) {
            return;
        }

        button.addEventListener('click', () => {
            document.body.classList.toggle('is-compact-mode');

            const icon = button.querySelector('i');

            if (icon) {
                icon.classList.toggle('ti-sun');
                icon.classList.toggle('ti-layout-sidebar-left-collapse');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initProfileDropdown();
        initActiveMenuFallback();
        initGlobalSearch();
        initThemeButton();
    });
})();