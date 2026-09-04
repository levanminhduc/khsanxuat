(function () {
    'use strict';

    var LS_DEPT = 'khsx_mobile_dept';
    var LS_MONTH = 'khsx_mobile_month';
    var LS_XUONG = 'khsx_mobile_xuong';
    var LS_ONLY_OPEN = 'khsx_mobile_only_open';
    var LS_CHART_OPEN = 'khsx_mobile_chart_open';
    var STATE_ORDER = { overdue: 0, soon: 1, normal: 2, done: 3 };
    var inited = false;

    function lsGet(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function lsSet(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {}
    }

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function todayYmd() {
        var d = new Date();
        return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
    }

    function formatDmy(ymd) {
        var parts = String(ymd || '').split('-');
        if (parts.length !== 3) {
            return '';
        }
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function daysUntil(deadline) {
        var today = todayYmd();
        var a = new Date(today + 'T00:00:00');
        var b = new Date(deadline + 'T00:00:00');
        return Math.round((b.getTime() - a.getTime()) / 86400000);
    }

    function parseDepts(card) {
        try {
            return JSON.parse(card.getAttribute('data-depts') || '{}');
        } catch (e) {
            return {};
        }
    }

    function deptMeta(depts, code) {
        for (var i = 0; i < depts.length; i++) {
            if (depts[i].code === code) {
                return depts[i];
            }
        }
        return null;
    }

    function isValidDept(depts, code) {
        return !!deptMeta(depts, code);
    }

    function computeState(info) {
        if (!info) {
            return { key: 'normal', label: '', deadline: '' };
        }
        var deadline = info.deadline || '';
        var dmy = formatDmy(deadline);
        if (info.done) {
            return { key: 'done', label: '✓ Xong ' + dmy, deadline: deadline };
        }
        var today = todayYmd();
        if (deadline < today) {
            return { key: 'overdue', label: 'Quá hạn ' + dmy, deadline: deadline };
        }
        var n = daysUntil(deadline);
        if (n <= 3) {
            return { key: 'soon', label: 'Còn ' + n + ' ngày · ' + dmy, deadline: deadline };
        }
        return { key: 'normal', label: 'Hạn ' + dmy, deadline: deadline };
    }

    function setHidden(el, hidden) {
        if (!el) {
            return;
        }
        if (hidden) {
            el.classList.add('is-hidden');
        } else {
            el.classList.remove('is-hidden');
        }
    }

    function setToggle(btn, on) {
        if (!btn) {
            return;
        }
        if (on) {
            btn.classList.add('is-on');
            btn.setAttribute('aria-pressed', 'true');
        } else {
            btn.classList.remove('is-on');
            btn.setAttribute('aria-pressed', 'false');
        }
    }

    function currentQuery() {
        return new URLSearchParams(window.location.search);
    }

    function replaceDeptParam(dept) {
        var params = currentQuery();
        if (dept) {
            params.set('dept', dept);
        } else {
            params.delete('dept');
        }
        var qs = params.toString();
        var url = window.location.pathname + (qs ? '?' + qs : '');
        history.replaceState(null, '', url);
    }

    function maybeInit() {
        if (inited) {
            return;
        }
        if (!window.matchMedia('(max-width: 768px)').matches) {
            return;
        }
        inited = true;
        init();
    }

    function init() {
        var rootConfig = window.indexConfig || {};
        var config = rootConfig.mobile || {};
        var depts = config.depts || [];
        var months = config.months || [];
        var xuongList = config.xuongList || [];
        var baseUrl = config.baseUrl || window.BASE_URL || '';

        var bar = document.querySelector('.mobile-dept__bar');
        var list = document.getElementById('mobileDeptList');
        if (!bar || !list) {
            return;
        }

        var deptBtn = document.getElementById('mobileDeptBtn');
        var deptValue = document.getElementById('mobileDeptValue');
        var monthSelect = document.getElementById('mobileMonthSelect');
        var xuongSelect = document.getElementById('mobileXuongSelect');
        var lineSelect = document.getElementById('mobileLineSelect');
        var emptyEl = document.getElementById('mobileDeptEmpty');
        var emptyBtn = document.getElementById('mobileDeptEmptyBtn');
        var noneEl = document.getElementById('mobileDeptNone');
        var countEl = document.getElementById('mobileDeptCount');
        var sheet = document.getElementById('mobileDeptSheet');
        var overlay = document.getElementById('mobileDeptOverlay');
        var chartToggle = document.getElementById('mobileChartToggle');
        var onlyOpenToggle = document.getElementById('mobileOnlyOpenToggle');
        var chartContainer = document.querySelector('.chart-container');
        var cards = Array.prototype.slice.call(list.querySelectorAll('.mobile-dept__card'));

        var storedXuong = lsGet(LS_XUONG) || '';
        if (xuongList.indexOf(storedXuong) === -1) {
            storedXuong = '';
        }

        var state = {
            dept: config.selectedDept || lsGet(LS_DEPT) || '',
            xuong: storedXuong,
            line: '',
            onlyOpen: lsGet(LS_ONLY_OPEN) === '1',
            chartOpen: lsGet(LS_CHART_OPEN) === '1'
        };

        if (!isValidDept(depts, state.dept)) {
            state.dept = '';
        }

        if (config.selectedDept && isValidDept(depts, config.selectedDept)) {
            lsSet(LS_DEPT, config.selectedDept);
        } else if (state.dept && !config.selectedDept) {
            replaceDeptParam(state.dept);
        }

        if (!currentQuery().has('month')) {
            var storedMonth = lsGet(LS_MONTH) || '';
            var match = storedMonth.match(/^(\d{4})-(\d{2})$/);
            if (match) {
                var sy = parseInt(match[1], 10);
                var sm = parseInt(match[2], 10);
                var exists = false;
                for (var i = 0; i < months.length; i++) {
                    if (months[i].month === sm && months[i].year === sy) {
                        exists = true;
                        break;
                    }
                }
                if (exists && (sm !== config.selectedMonth || sy !== config.selectedYear)) {
                    var monthUrl = baseUrl + '/index.php?month=' + sm + '&year=' + sy;
                    if (state.dept) {
                        monthUrl += '&dept=' + encodeURIComponent(state.dept);
                    }
                    window.location.replace(monthUrl);
                    return;
                }
            }
        }

        function updateDeptButton() {
            var meta = deptMeta(depts, state.dept);
            if (!meta) {
                deptValue.textContent = 'Chọn bộ phận của bạn…';
                deptValue.classList.add('is-placeholder');
                return;
            }
            deptValue.textContent = meta.label;
            deptValue.classList.remove('is-placeholder');
        }

        function updateSheetSelection() {
            var rows = bar.querySelectorAll('.mobile-dept__sheet-row');
            for (var r = 0; r < rows.length; r++) {
                if (rows[r].getAttribute('data-dept') === state.dept) {
                    rows[r].classList.add('is-selected');
                } else {
                    rows[r].classList.remove('is-selected');
                }
            }
        }

        function rebuildLines() {
            var seen = {};
            var lines = [];
            cards.forEach(function (card) {
                if (state.xuong && card.getAttribute('data-xuong') !== state.xuong) {
                    return;
                }
                var line = card.getAttribute('data-line') || '';
                if (!line || seen[line]) {
                    return;
                }
                seen[line] = true;
                lines.push(line);
            });
            lines.sort(function (a, b) {
                return (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0);
            });

            var html = '<option value="">Tất cả</option>';
            for (var l = 0; l < lines.length; l++) {
                var safe = String(lines[l])
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;');
                html += '<option value="' + safe + '">' + safe + '</option>';
            }
            lineSelect.innerHTML = html;
            state.line = '';
            lineSelect.value = '';
        }

        function openSheet() {
            sheet.classList.add('is-open');
            document.body.classList.add('is-mobile-sheet-open');
        }

        function closeSheet() {
            sheet.classList.remove('is-open');
            document.body.classList.remove('is-mobile-sheet-open');
        }

        function setDept(dept) {
            state.dept = dept;
            lsSet(LS_DEPT, dept);
            replaceDeptParam(dept);
            updateDeptButton();
            updateSheetSelection();
            render();
        }

        function render() {
            var hasDept = !!state.dept;
            setHidden(emptyEl, hasDept);
            setHidden(list, !hasDept);

            if (!hasDept) {
                if (countEl) {
                    countEl.textContent = '';
                }
                setHidden(noneEl, true);
                return;
            }

            var filtered = [];
            cards.forEach(function (card) {
                var xuong = card.getAttribute('data-xuong') || '';
                var line = card.getAttribute('data-line') || '';
                var matchXuong = !state.xuong || xuong === state.xuong;
                var matchLine = !state.line || line === state.line;
                var info = parseDepts(card)[state.dept];
                var computed = computeState(info);
                var stt = parseInt(card.getAttribute('data-stt'), 10) || 0;

                card.classList.remove('is-done', 'is-overdue', 'is-soon', 'is-all-done');
                card.classList.add('is-' + computed.key);
                if (card.getAttribute('data-all-done') === '1') {
                    card.classList.add('is-all-done');
                }

                var statusEl = card.querySelector('.mobile-dept__status');
                if (statusEl) {
                    statusEl.textContent = computed.label;
                }

                card.href = baseUrl + '/indexdept.php?dept=' + encodeURIComponent(state.dept) + '&id=' + encodeURIComponent(card.getAttribute('data-stt'));

                if (matchXuong && matchLine) {
                    filtered.push({
                        card: card,
                        key: computed.key,
                        deadline: computed.deadline || '9999-99-99',
                        stt: stt,
                        done: computed.key === 'done'
                    });
                } else {
                    card.classList.add('is-hidden');
                }
            });

            filtered.sort(function (a, b) {
                var oa = STATE_ORDER[a.key];
                var ob = STATE_ORDER[b.key];
                if (oa !== ob) {
                    return oa - ob;
                }
                if (a.deadline !== b.deadline) {
                    return a.deadline < b.deadline ? -1 : 1;
                }
                return a.stt - b.stt;
            });

            var n = filtered.length;
            var m = 0;
            var visible = 0;
            filtered.forEach(function (item) {
                if (!item.done) {
                    m += 1;
                }
                var hide = state.onlyOpen && item.done;
                if (hide) {
                    item.card.classList.add('is-hidden');
                } else {
                    item.card.classList.remove('is-hidden');
                    list.appendChild(item.card);
                    visible += 1;
                }
            });
            if (noneEl) {
                list.appendChild(noneEl);
            }

            if (countEl) {
                if (state.line) {
                    countEl.textContent = 'Line ' + state.line + ' · ' + m + ' / ' + n + ' chưa xong';
                } else {
                    countEl.textContent = n + ' · ' + m + ' chưa xong';
                }
            }

            setHidden(noneEl, visible > 0);
        }

        updateDeptButton();
        updateSheetSelection();
        if (xuongSelect) {
            xuongSelect.value = state.xuong;
        }
        function updateChartVisibility() {
            setToggle(chartToggle, state.chartOpen);
            if (!chartContainer) {
                return;
            }
            if (state.chartOpen) {
                chartContainer.classList.add('is-open');
                window.requestAnimationFrame(function () {
                    if (!window.IndexPage || !window.IndexPage.chart) {
                        if (window.IndexPage && typeof window.IndexPage.initChart === 'function') {
                            window.IndexPage.initChart();
                        }
                    } else if (typeof window.IndexPage.chart.resize === 'function') {
                        window.IndexPage.chart.resize();
                    }
                });
            } else {
                chartContainer.classList.remove('is-open');
            }
        }

        rebuildLines();
        setToggle(onlyOpenToggle, state.onlyOpen);
        updateChartVisibility();
        render();

        if (deptBtn) {
            deptBtn.addEventListener('click', openSheet);
        }
        if (emptyBtn) {
            emptyBtn.addEventListener('click', openSheet);
        }
        if (overlay) {
            overlay.addEventListener('click', closeSheet);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sheet.classList.contains('is-open')) {
                closeSheet();
            }
        });

        bar.addEventListener('click', function (e) {
            var row = e.target.closest('.mobile-dept__sheet-row');
            if (!row) {
                return;
            }
            var code = row.getAttribute('data-dept') || '';
            if (!isValidDept(depts, code)) {
                return;
            }
            setDept(code);
            closeSheet();
        });

        if (monthSelect) {
            monthSelect.addEventListener('change', function () {
                var option = monthSelect.options[monthSelect.selectedIndex];
                var month = monthSelect.value;
                var year = option ? option.getAttribute('data-year') : '';
                lsSet(LS_MONTH, year + '-' + pad2(parseInt(month, 10)));
                var url = baseUrl + '/index.php?month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);
                if (state.dept) {
                    url += '&dept=' + encodeURIComponent(state.dept);
                }
                window.location.href = url;
            });
        }

        if (xuongSelect) {
            xuongSelect.addEventListener('change', function () {
                state.xuong = xuongSelect.value || '';
                lsSet(LS_XUONG, state.xuong);
                rebuildLines();
                render();
            });
        }

        if (lineSelect) {
            lineSelect.addEventListener('change', function () {
                state.line = lineSelect.value || '';
                render();
            });
        }

        if (onlyOpenToggle) {
            onlyOpenToggle.addEventListener('click', function () {
                state.onlyOpen = !state.onlyOpen;
                lsSet(LS_ONLY_OPEN, state.onlyOpen ? '1' : '0');
                setToggle(onlyOpenToggle, state.onlyOpen);
                render();
            });
        }

        if (chartToggle && chartContainer) {
            chartToggle.addEventListener('click', function () {
                state.chartOpen = !state.chartOpen;
                lsSet(LS_CHART_OPEN, state.chartOpen ? '1' : '0');
                updateChartVisibility();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        maybeInit();
        window.addEventListener('resize', maybeInit);
    });
})();
