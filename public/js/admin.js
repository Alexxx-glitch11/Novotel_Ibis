(() => {
    document.querySelectorAll('[data-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.toggle);
            if (target) target.hidden = !target.hidden;
        });
    });

    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
        document.querySelector('[data-sidebar]')?.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {
        const sidebar = document.querySelector('[data-sidebar]');
        if (sidebar?.classList.contains('is-open') && !sidebar.contains(event.target) && !event.target.closest('[data-sidebar-toggle]')) {
            sidebar.classList.remove('is-open');
        }
    });
})();
