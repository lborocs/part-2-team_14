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
       SPECIALTIES: See more / See less
    ---------------------------------- */
    function initSpecialties() {
        document.querySelectorAll('.employee-card').forEach(card => {
            const container = card.querySelector('.specialties-container');
            const btn = card.querySelector('.see-more-btn');

            if (!container || !btn) return;

            // Only show button if content overflows
            if (container.scrollHeight > container.clientHeight) {
                btn.hidden = false;
            } else {
                btn.hidden = true;
            }

            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // prevent card click
                container.classList.toggle('expanded');
                btn.textContent = container.classList.contains('expanded')
                    ? 'See less'
                    : 'See more';
            });
        });
    }

    // Run once on initial page load
    initSpecialties();

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

                // IMPORTANT: re-init specialties on new cards
                initSpecialties();
            });
    });

});

