(function() {
    function initRepeater() {
        var table = document.getElementById('lpgc-aff-pairs-table');
        if (!table) {
            return;
        }

        var addButton = document.getElementById('lpgc-add-row');
        if (addButton) {
            addButton.addEventListener('click', function() {
                var tbody = table.querySelector('tbody');
                if (!tbody || !window.LPGCAdmin || !LPGCAdmin.newRowHTML) {
                    return;
                }
                var index = Date.now();
                var html = LPGCAdmin.newRowHTML.replace(/__INDEX__/g, index.toString());
                var temp = document.createElement('tbody');
                temp.innerHTML = html;
                var row = temp.firstElementChild;
                if (row) {
                    tbody.appendChild(row);
                }
            });
        }

        table.addEventListener('click', function(event) {
            var target = event.target;
            if (target && target.classList.contains('lpgc-remove-row')) {
                event.preventDefault();
                var row = target.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRepeater);
    } else {
        initRepeater();
    }
})();
