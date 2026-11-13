// Beginner Note: These small helpers make the sidebar and forms nicer to use on every screen size.
(() => {
    const shell = document.getElementById('app-shell');
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (shell && toggle) {
        if (localStorage.getItem('ais-sidebar-collapsed') === 'true')
            shell.classList.add('collapsed');
        toggle.addEventListener('click', () => {
            shell.classList.toggle('collapsed');
            const expanded = !shell.classList.contains('collapsed');
            toggle.setAttribute('aria-expanded', String(expanded));
            toggle.setAttribute(
                'aria-label',
                expanded ? 'Collapse navigation' : 'Expand navigation',
            );
            localStorage.setItem('ais-sidebar-collapsed', String(!expanded));
        });
    }

    function closeMobileNav() {
        sidebar?.classList.remove('mobile-open');
        backdrop?.classList.remove('visible');
        mobileMenu?.setAttribute('aria-expanded', 'false');
    }
    mobileMenu?.addEventListener('click', () => {
        sidebar?.classList.add('mobile-open');
        backdrop?.classList.add('visible');
        mobileMenu.setAttribute('aria-expanded', 'true');
        sidebar?.querySelector('a')?.focus();
    });
    backdrop?.addEventListener('click', closeMobileNav);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMobileNav();
    });
    sidebar
        ?.querySelectorAll('.nav-link')
        .forEach((link) => link.addEventListener('click', closeMobileNav));

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm') || 'Continue with this action?'))
                event.preventDefault();
        });
    });
    document
        .querySelectorAll('.alert-close')
        .forEach((button) =>
            button.addEventListener('click', () => button.closest('.alert')?.remove()),
        );
    document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
    document.querySelector('[data-page-size]')?.addEventListener('change', (event) => {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', event.target.value);
        url.searchParams.set('p', '1');
        window.location.assign(url.toString());
    });

    const version = Number(document.body.dataset.version || 0);
    const notice = document.getElementById('refresh-notice');
    const dirtyForms = new WeakSet();
    document.querySelectorAll('form.record-form').forEach((form) => {
        form.addEventListener('input', () => dirtyForms.add(form));
        form.addEventListener('change', () => dirtyForms.add(form));
    });
    function handleNewData() {
        const editing = [...document.querySelectorAll('form.record-form')].some((form) =>
            dirtyForms.has(form),
        );
        if (editing) {
            if (notice) notice.hidden = false;
        } else window.location.reload();
    }
    const channel =
        'BroadcastChannel' in window ? new BroadcastChannel('ais-record-updates') : null;
    channel?.addEventListener('message', (event) => {
        if (event.data?.type === 'changed') handleNewData();
    });
    if (document.querySelector('.alert-success')) channel?.postMessage({ type: 'changed' });
    document
        .querySelector('[data-refresh]')
        ?.addEventListener('click', () => window.location.reload());
    if (notice) {
        window.setInterval(async () => {
            try {
                const response = await fetch('index.php?page=freshness', {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const data = await response.json();
                if (Number(data.version) > version) handleNewData();
            } catch (_) {
                /* A temporary connection issue should not interrupt editing. */
            }
        }, 30000);
    }

    const secureLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    if (
        'serviceWorker' in navigator &&
        (window.location.protocol === 'https:' || secureLocalhost)
    ) {
        window.addEventListener('load', () =>
            navigator.serviceWorker.register('sw.js').catch(() => {}),
        );
    }
})();
