/**
 * AWS Dashboard – Client-side JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Profile / region selector auto-submit ----
    document.querySelectorAll('.auto-submit').forEach(function (el) {
        el.addEventListener('change', function () {
            this.closest('form').submit();
        });
    });

    // ---- Confirmation modal ----
    window.confirmAction = function (title, message, formId) {
        var overlay = document.getElementById('confirm-modal');
        if (!overlay) return;
        overlay.querySelector('.modal-title').textContent = title;
        overlay.querySelector('.modal-message').textContent = message;
        overlay.classList.add('show');

        var confirmBtn = overlay.querySelector('.btn-confirm');
        // Clone to remove old event listeners
        var newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

        newBtn.addEventListener('click', function () {
            document.getElementById(formId).submit();
            overlay.classList.remove('show');
        });
    };

    window.closeModal = function () {
        var overlay = document.getElementById('confirm-modal');
        if (overlay) overlay.classList.remove('show');
    };

    // Close modal on overlay click
    var overlay = document.getElementById('confirm-modal');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    // ---- Table sort ----
    document.querySelectorAll('table.data-table th[data-sort]').forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var table = th.closest('table');
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr'));
            var col = Array.from(th.parentNode.children).indexOf(th);
            var type = th.dataset.sort; // 'string', 'number', 'date'
            var asc = th.classList.contains('sort-asc');

            rows.sort(function (a, b) {
                var aVal = a.children[col] ? a.children[col].textContent.trim() : '';
                var bVal = b.children[col] ? b.children[col].textContent.trim() : '';
                if (type === 'number') {
                    aVal = parseFloat(aVal.replace(/[^0-9.\-]/g, '')) || 0;
                    bVal = parseFloat(bVal.replace(/[^0-9.\-]/g, '')) || 0;
                    return asc ? bVal - aVal : aVal - bVal;
                }
                if (type === 'date') {
                    aVal = new Date(aVal).getTime() || 0;
                    bVal = new Date(bVal).getTime() || 0;
                    return asc ? bVal - aVal : aVal - bVal;
                }
                return asc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
            });

            // Update sort indicator
            th.parentNode.querySelectorAll('th').forEach(function (h) {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            th.classList.add(asc ? 'sort-desc' : 'sort-asc');

            rows.forEach(function (row) { tbody.appendChild(row); });
        });
    });
});
