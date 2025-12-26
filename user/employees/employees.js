document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('dblclick', (e) => {
            if (e.target.closest('a')) return; // ignore links
            const url = card.dataset.profileUrl;
            if (url) window.location.href = url;
        });
    });
});
