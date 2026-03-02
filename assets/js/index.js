(function() {
    'use strict';

    var config = window.indexConfig || {};

    function getDeptCode(deptName) {
        if (config.chartDepartments && config.chartDepartments[deptName]) {
            return config.chartDepartments[deptName].code;
        }
        var deptMap = {
            'Kế Hoạch': 'kehoach',
            'Kỹ Thuật': 'chuanbi_sanxuat_phong_kt',
            'Kho': 'kho',
            'Cắt': 'cat',
            'Ép Keo': 'ep_keo',
            'Cơ Điện': 'co_dien',
            'Chuyền May': 'chuyen_may',
            'KCS': 'kcs',
            'Ủi TP': 'ui_thanh_pham',
            'Hoàn Thành': 'hoan_thanh'
        };
        return deptMap[deptName];
    }

    function initSelectAllCheckbox() {
        var selectAll = document.getElementById('selectAll') || document.getElementById('select-all');
        if (!selectAll) return;

        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.row-checkbox, input[name="selected_rows[]"]');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
                highlightRow(cb);
            });
        });
    }

    function initRowHighlight() {
        var checkboxes = document.querySelectorAll('.row-checkbox, input[name="selected_rows[]"]');
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                highlightRow(cb);
            });
        });
    }

    function highlightRow(checkbox) {
        var row = checkbox.closest('tr');
        if (!row) return;

        if (checkbox.checked) {
            row.classList.add('selected-row');
            row.style.backgroundColor = '#f0f9ff';
        } else {
            row.classList.remove('selected-row');
            row.style.backgroundColor = '';
        }
    }

    function initDragToScroll() {
        var container = document.querySelector('.data-table-container');
        if (!container) return;

        var isDragging = false;
        var startX, scrollLeft;

        container.style.cursor = 'grab';

        container.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return;
            if (['A', 'BUTTON', 'INPUT', 'SELECT'].indexOf(e.target.tagName) !== -1) return;

            isDragging = true;
            container.style.cursor = 'grabbing';
            container.style.userSelect = 'none';
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
            e.preventDefault();
        });

        container.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var x = e.pageX - container.offsetLeft;
            var walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        });

        function endDrag() {
            if (!isDragging) return;
            isDragging = false;
            container.style.cursor = 'grab';
            container.style.userSelect = '';
        }

        container.addEventListener('mouseup', endDrag);
        container.addEventListener('mouseleave', endDrag);
    }

    function initCheckboxScroll() {
        var container = document.querySelector('.data-table-container');
        if (!container) return;

        var checkboxes = document.querySelectorAll('.data-table tbody input[type="checkbox"]');
        var selectAll = document.getElementById('selectAll') || document.getElementById('select-all');

        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var selectedCount = document.querySelectorAll('.data-table tbody input[type="checkbox"]:checked').length;

                if (selectedCount === 1) {
                    scrollToRight(container);
                } else {
                    scrollToLeft(container);
                }
            });
        });

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                scrollToLeft(container);
            });
        }
    }

    function scrollToRight(container) {
        container.scrollTo({ left: container.scrollWidth, behavior: 'smooth' });
    }

    function scrollToLeft(container) {
        container.scrollTo({ left: 0, behavior: 'smooth' });
    }

    function initChart() {
        var canvas = document.getElementById('departmentChart');
        if (!canvas || typeof Chart === 'undefined') return;
        if (!config.deptStats) return;

        var ctx = canvas.getContext('2d');
        var isMobile = window.innerWidth <= 428;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: config.deptStats.labels,
                datasets: [{
                    label: 'Tỷ lệ hoàn thành (%)',
                    data: config.deptStats.data,
                    backgroundColor: config.deptStats.colors,
                    borderRadius: 8,
                    maxBarThickness: 50,
                    borderColor: 'rgba(255, 255, 255, 0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 20, right: 20, bottom: 20, left: 20 }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Tỷ lệ hoàn thành - Tháng ' + config.selectedMonth + '/' + config.selectedYear,
                        font: {
                            size: isMobile ? 16 : 24,
                            weight: 'bold',
                            family: "'Segoe UI', 'Arial', sans-serif"
                        },
                        padding: isMobile ? 10 : 20,
                        color: 'rgb(226, 2, 2)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        },
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#333',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true
                    }
                },
                onClick: function(e, elements) {
                    if (elements.length > 0) {
                        var index = elements[0].index;
                        var deptName = this.data.labels[index];
                        var deptCode = getDeptCode(deptName);
                        if (deptCode) {
                            window.location.href = 'dept_statistics.php?dept=' + deptCode +
                                '&month=' + config.selectedMonth + '&year=' + config.selectedYear;
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0, 0, 0, 0.1)', drawBorder: false },
                        ticks: {
                            callback: function(value) { return value + '%'; },
                            font: { size: isMobile ? 10 : 12 },
                            color: '#666'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: isMobile ? 8 : 12 },
                            color: '#666',
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                hover: { mode: 'index', intersect: false }
            }
        });
    }

    window.changeMonth = function(select) {
        var option = select.options[select.selectedIndex];
        var month = select.value;
        var year = option.getAttribute('data-year');

        var params = new URLSearchParams(window.location.search);
        var searchXuong = params.get('search_xuong');

        var currentPage = window.location.pathname.split('/').pop() || 'index.php';
        var url = currentPage + '?month=' + month + '&year=' + year;
        if (searchXuong) {
            url += '&search_xuong=' + encodeURIComponent(searchXuong);
        }

        window.location.href = url;
    };

    // Initialize all on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initSelectAllCheckbox();
        initRowHighlight();
        initDragToScroll();
        initCheckboxScroll();
        initChart();
    });

})();
