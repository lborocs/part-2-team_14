document.addEventListener('DOMContentLoaded', () => {

    /* ---------------------------------
    Shared AJAX fetch + replace helper
    ---------------------------------- */
    function fetchAndReplace(url) {
    url.searchParams.set('ajax', '1');

    return fetch(url)
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

        url.searchParams.delete('ajax');
        window.history.pushState({}, '', url.toString());

        feather.replace();
        initSpecialties();
        });
    }


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
                btn.textContent = '...'; // INITIAL STATE
            } else {
                btn.hidden = true;
            }


            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // prevent card click

                const isExpanded = container.classList.toggle('expanded');

                btn.textContent = isExpanded ? 'Hide' : '...';
            });

        });
    }

    // Run once on initial page load
    initSpecialties();

    /* ---------------------------------
    FILTER PANEL TOGGLE
    ---------------------------------- */
    const filterToggle = document.getElementById('filter-toggle');
    const filterPanel  = document.getElementById('filter-panel');

    if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = filterPanel.hasAttribute('hidden');
        if (isHidden) filterPanel.removeAttribute('hidden');
        else filterPanel.setAttribute('hidden', '');
    });

    // close on outside click
    document.addEventListener('click', (e) => {
        if (filterPanel.hasAttribute('hidden')) return;
        if (e.target.closest('#filter-panel') || e.target.closest('#filter-toggle')) return;
        filterPanel.setAttribute('hidden', '');
    });

    // close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
        filterPanel.setAttribute('hidden', '');
        }
    });
    }

    // apply filters 
    function applyFilters() {
        const url = new URL(window.location.href);

        // reset existing filter params - DELETE BOTH FORMATS
        url.searchParams.delete('specialty');
        url.searchParams.delete('specialty[]');  // ← Add this line!
        url.searchParams.delete('project');
        url.searchParams.delete('project[]');    // ← Add this line!

        // specialties - collect as array
        const selectedSpecs = Array.from(document.querySelectorAll('.filter-specialty:checked'))
            .map(cb => cb.value);
        
        selectedSpecs.forEach(spec => {
            url.searchParams.append('specialty[]', spec);
        });

        // projects
        const selectedProjs = Array.from(document.querySelectorAll('.filter-project:checked'))
            .map(cb => cb.value);
        
        selectedProjs.forEach(proj => {
            url.searchParams.append('project[]', proj);
        });

        // reset to page 1
        url.searchParams.set('page', '1');

        fetchAndReplace(url);
    }

    /* ---------------------------------
    CLEAR FILTERS
    ---------------------------------- */
    const clearBtn = document.getElementById('filter-clear');
    if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        document
        .querySelectorAll('.filter-specialty, .filter-project')
        .forEach(cb => cb.checked = false);

        applyFilters();
    });
    }

    /* ---------------------------------
    APPLY FILTERS BUTTON
    ---------------------------------- */
    const applyBtn = document.getElementById('filter-apply');
    if (applyBtn) {
    applyBtn.addEventListener('click', () => {
        applyFilters();

        // close the panel after applying
        filterPanel?.setAttribute('hidden', '');
    });
    }


    /* ---------------------------------
    FILTER SEARCH (within panel)
    ---------------------------------- */
    function wireFilterSearch(searchId, listId) {
    const search = document.getElementById(searchId);
    const list = document.getElementById(listId);
    if (!search || !list) return;

    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        list.querySelectorAll('.filter-check').forEach(row => {
        const text = row.textContent.trim().toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
        });
    });
    }

    wireFilterSearch('filter-specialty-search', 'filter-specialty-list');
    wireFilterSearch('filter-project-search', 'filter-project-list');

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

    /* ---------------------------------
    SORT DROPDOWN (same as Projects page)
    ---------------------------------- */
    const sortSelect = document.getElementById('sortEmployees');

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);

            url.searchParams.set('sort', sortSelect.value);
            url.searchParams.set('page', '1');
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

                    url.searchParams.delete('ajax');
                    window.history.pushState({}, '', url.toString());

                    feather.replace();
                    initSpecialties();
                });
        });
    }

});

// Clear filters on page refresh (not on back/forward navigation)
window.addEventListener('beforeunload', () => {
    // Mark that we're about to refresh
    sessionStorage.setItem('clearFiltersOnLoad', 'true');
});

window.addEventListener('load', () => {
    // Check if we should clear filters
    if (sessionStorage.getItem('clearFiltersOnLoad') === 'true') {
        sessionStorage.removeItem('clearFiltersOnLoad');
        
        const url = new URL(window.location.href);
        
        // Remove filter parameters
        url.searchParams.delete('specialty');
        url.searchParams.delete('specialty[]');
        url.searchParams.delete('project');
        url.searchParams.delete('project[]');
        
        // If filters were present, reload without them
        const originalSearch = window.location.search;
        if (originalSearch !== url.search) {
            window.location.replace(url.toString());
        }
    }
});

