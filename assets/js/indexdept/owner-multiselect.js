(function () {
    'use strict';

    function renderChips(root) {
        var chipsBox = root.querySelector('[data-owner-chips]');
        var panel = root.querySelector('[data-owner-panel]');
        if (!chipsBox || !panel) {
            return;
        }

        var checked = panel.querySelectorAll('input[type="checkbox"]:checked');
        chipsBox.innerHTML = '';

        if (checked.length === 0) {
            var empty = document.createElement('span');
            empty.className = 'owner-select__placeholder';
            empty.textContent = 'Chưa phân công';
            chipsBox.appendChild(empty);
            return;
        }

        checked.forEach(function (cb) {
            var label = cb.closest('.owner-checklist__item');
            var nameEl = label ? label.querySelector('.owner-checklist__name') : null;
            var name = nameEl ? nameEl.textContent : cb.value;

            var chip = document.createElement('span');
            chip.className = 'owner-chip';
            chip.textContent = name;
            chipsBox.appendChild(chip);
        });
    }

    function syncItemState(cb) {
        var label = cb.closest('.owner-checklist__item');
        if (!label) {
            return;
        }
        label.classList.toggle('owner-checklist__item--checked', cb.checked);
    }

    function openPanel(root) {
        var panel = root.querySelector('[data-owner-panel]');
        if (panel) {
            panel.hidden = false;
            root.classList.add('owner-select--open');
        }
    }

    function closePanel(root) {
        var panel = root.querySelector('[data-owner-panel]');
        if (panel) {
            panel.hidden = true;
            root.classList.remove('owner-select--open');
        }
    }

    function initOwnerSelects() {
        var roots = document.querySelectorAll('[data-owner-select]');

        roots.forEach(function (root) {
            // Render chip ban đầu từ checkbox đã checked sẵn theo dữ liệu DB
            renderChips(root);

            var toggle = root.querySelector('[data-owner-toggle]');
            if (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var panel = root.querySelector('[data-owner-panel]');
                    if (panel && panel.hidden) {
                        openPanel(root);
                    } else {
                        closePanel(root);
                    }
                });
            }

            root.addEventListener('change', function (e) {
                if (e.target && e.target.type === 'checkbox') {
                    syncItemState(e.target);
                    renderChips(root);
                }
            });

            // Chặn click trong panel nổi bọt ra document (tránh tự đóng panel)
            var panel = root.querySelector('[data-owner-panel]');
            if (panel) {
                panel.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('[data-owner-select].owner-select--open').forEach(function (root) {
                closePanel(root);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOwnerSelects);
    } else {
        initOwnerSelects();
    }
})();
