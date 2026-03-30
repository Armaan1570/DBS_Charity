// main.js — CharityHub

// Modal helpers
function openModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            closeModal(m.id);
        });
    }
});

// Table navigation (first/last/search)
window.tableNav = {
    current: 0,
    rows: [],
    init: function(tableId) {
        this.rows = Array.from(document.querySelectorAll('#' + tableId + ' tbody tr'));
    },
    goFirst: function() {
        if (this.rows.length > 0) {
            this.rows.forEach(r => r.classList.remove('highlight-row'));
            this.rows[0].classList.add('highlight-row');
            this.rows[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    },
    goLast: function() {
        const visible = this.rows.filter(r => r.style.display !== 'none');
        if (visible.length > 0) {
            this.rows.forEach(r => r.classList.remove('highlight-row'));
            visible[visible.length - 1].classList.add('highlight-row');
            visible[visible.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
};

// Search / filter table rows
function filterTable(inputId, tableId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Highlight style for nav
const style = document.createElement('style');
style.textContent = '.highlight-row td { background: rgba(201,168,76,0.07) !important; }';
document.head.appendChild(style);

// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(() => el.remove(), 500); }, 4000);
});

// Animate progress bars on load
document.querySelectorAll('.progress-fill').forEach(bar => {
    const target = bar.dataset.width || '0';
    bar.style.width = '0';
    setTimeout(() => { bar.style.width = target + '%'; }, 200);
});

// Populate edit modal fields dynamically
function populateEdit(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    Object.entries(data).forEach(([key, val]) => {
        const el = modal.querySelector('[name="' + key + '"]');
        if (el) el.value = val;
    });
    openModal(modalId);
}
