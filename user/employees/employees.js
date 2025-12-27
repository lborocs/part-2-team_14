document.addEventListener('DOMContentLoaded', () => {

    /* ---------------------------------
       Double-click employee card → profile
    ---------------------------------- */
    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('dblclick', (e) => {
            if (e.target.closest('a, button')) return;
            const url = card.dataset.profileUrl;
            if (url) window.location.href = url;
        });
    });

    /* ---------------------------------
       AJAX pagination (no page reload)
    ---------------------------------- */
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.pagination-btn, .pagination-page');
        if (!link || !link.href) return;

        e.preventDefault();

        const url = new URL(link.href);
        url.searchParams.set('ajax', '1');

        fetch(url)
            .then(res => res.text())
            .then(html => {
                const temp = document.createElement('div');
                temp.innerHTML = html;

                document.getElementById('employees-count')?.replaceWith(
                    temp.querySelector('#employees-count')
                );
                document.getElementById('employee-grid')?.replaceWith(
                    temp.querySelector('#employee-grid')
                );
                document.getElementById('employees-pagination')?.replaceWith(
                    temp.querySelector('#employees-pagination')
                );

                // Update URL without reload
                window.history.pushState({}, '', link.href);

                // Re-init feather icons (new DOM)
                feather.replace();
            });
    });

});
