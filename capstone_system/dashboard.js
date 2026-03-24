/**
 * BayanTap – Dashboard JavaScript
 * Handles search debounce, row click, animations.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Live Search Debounce ────────────────────────────────
    const searchInput = document.getElementById('searchInput');
    let searchTimer;

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 450);
        });
    }

    // ── Bill Row Click → Receipt Preview ───────────────────
    document.querySelectorAll('.bill-row').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', (e) => {
            // Don't trigger if clicking the action button link
            if (e.target.closest('a')) return;

            const billId = row.dataset.billId;
            const params = new URLSearchParams(window.location.search);
            params.set('receipt', billId);

            // Navigate and scroll to receipt panel
            window.location.href = '?' + params.toString() + '#receipt-panel';
        });
    });

    // ── Highlight active row ────────────────────────────────
    const activeRow = document.querySelector('.row-active');
    if (activeRow) {
        activeRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── Animate summary cards on load ──────────────────────
    const cards = document.querySelectorAll('.summary-card');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + i * 80);
    });

    // ── Animate table rows ──────────────────────────────────
    const rows = document.querySelectorAll('.bill-row');
    rows.forEach((row, i) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-8px)';
        setTimeout(() => {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 150 + i * 40);
    });

    // ── Animate counter values ──────────────────────────────
    document.querySelectorAll('.card-value').forEach(el => {
        const target = parseInt(el.textContent.replace(/,/g, ''), 10);
        if (isNaN(target) || target === 0) return;

        let start = 0;
        const duration = 800;
        const step = duration / 60;
        const increment = target / (duration / step);

        el.textContent = '0';
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                el.textContent = target.toLocaleString();
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(start).toLocaleString();
            }
        }, step);
    });

    // ── Scroll-to receipt panel on mobile ──────────────────
    if (window.location.hash === '#receipt-panel') {
        const panel = document.getElementById('receipt-panel');
        if (panel) {
            setTimeout(() => {
                panel.scrollIntoView({ behavior: 'smooth' });
            }, 300);
        }
    }

});
