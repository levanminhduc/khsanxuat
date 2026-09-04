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
            'Hoàn Thành': 'hoan_thanh',
            'Trung Tâm BTP': 'trung_tam_btp',
            'TT BTP': 'trung_tam_btp'
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

    var mobileBarLabelsPlugin = {
        id: 'mobileBarLabels',
        afterDatasetsDraw: function(chart) {
            if (!chart.options || chart.options.indexAxis !== 'y') {
                return;
            }
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function(dataset, datasetIndex) {
                var meta = chart.getDatasetMeta(datasetIndex);
                if (!meta || meta.hidden) return;
                meta.data.forEach(function(element, index) {
                    var val = dataset.data[index];
                    if (val === undefined || val === null) return;
                    var text = val + '%';
                    ctx.save();
                    ctx.font = '600 11px sans-serif';
                    ctx.textBaseline = 'middle';
                    var textWidth = ctx.measureText(text).width;
                    var xPos = element.x + 4;
                    if (xPos + textWidth > chart.width - 4) {
                        xPos = Math.max(element.base + 4, element.x - textWidth - 4);
                        ctx.fillStyle = (element.x - element.base > 36) ? '#ffffff' : '#1e293b';
                    } else {
                        ctx.fillStyle = '#1e293b';
                    }
                    ctx.fillText(text, xPos, element.y);
                    ctx.restore();
                });
            });
        }
    };

    function handleChartClick(e, elements) {
        if (elements && elements.length > 0) {
            var index = elements[0].index;
            var deptName = this.data.labels[index];
            var deptCode = getDeptCode(deptName);
            if (deptCode) {
                var baseUrl = window.BASE_URL || '';
                window.location.href = baseUrl + '/pages/dept_statistics.php?dept=' + encodeURIComponent(deptCode) +
                    '&month=' + encodeURIComponent(config.selectedMonth) + '&year=' + encodeURIComponent(config.selectedYear);
            }
        }
    }

    function initChart() {
        var canvas = document.getElementById('departmentChart');
        if (!canvas || typeof Chart === 'undefined') return;
        if (!config.deptStats) return;

        var isMobile = window.matchMedia('(max-width: 768px)').matches;
        var container = canvas.parentElement;

        window.IndexPage = window.IndexPage || {};

        if (isMobile && container) {
            var deptCount = (config.deptStats.labels && config.deptStats.labels.length) ? config.deptStats.labels.length : 11;
            container.style.height = (deptCount * 24 + 54) + 'px';
        } else if (container) {
            container.style.height = '';
        }

        if (window.IndexPage.chart) {
            if (window.IndexPage.chartIsMobile === isMobile) {
                return window.IndexPage.chart;
            }
            window.IndexPage.chart.destroy();
            window.IndexPage.chart = null;
        }

        var ctx = canvas.getContext('2d');
        window.IndexPage.chartIsMobile = isMobile;

        var chartOptions;
        if (isMobile) {
            chartOptions = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 2, right: 28, bottom: 2, left: 0 }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Tỷ lệ hoàn thành - Tháng ' + config.selectedMonth + '/' + config.selectedYear,
                        font: {
                            size: 12,
                            weight: 'bold',
                            family: "'Segoe UI', 'Arial', sans-serif"
                        },
                        padding: { top: 2, bottom: 6 },
                        color: 'rgb(226, 2, 2)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        },
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1e293b',
                        bodyColor: '#1e293b',
                        borderColor: '#cbd5e1',
                        borderWidth: 1,
                        padding: 8,
                        boxPadding: 4,
                        usePointStyle: true
                    }
                },
                onClick: handleChartClick,
                scales: {
                    x: {
                        beginAtZero: true,
                        min: 0,
                        max: 100,
                        border: { display: false },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.06)',
                            drawBorder: false,
                            drawTicks: false
                        },
                        ticks: {
                            stepSize: 50,
                            autoSkip: false,
                            callback: function(value) { return value + '%'; },
                            font: { size: 10 },
                            color: '#64748b',
                            padding: 2
                        }
                    },
                    y: {
                        border: { display: false },
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            font: { size: 10.5, weight: '600' },
                            color: '#1e293b',
                            maxRotation: 0,
                            minRotation: 0,
                            padding: 3,
                            callback: function(val) {
                                var label = typeof val === 'string' ? val : (this.getLabelForValue ? this.getLabelForValue(val) : val);
                                if (label === 'Trung Tâm BTP') {
                                    return 'TT BTP';
                                }
                                return label;
                            }
                        }
                    }
                },
                animation: { duration: 800, easing: 'easeInOutQuart' },
                hover: { mode: 'index', intersect: false }
            };
        } else {
            chartOptions = {
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
                            size: 24,
                            weight: 'bold',
                            family: "'Segoe UI', 'Arial', sans-serif"
                        },
                        padding: 20,
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
                onClick: handleChartClick,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(0, 0, 0, 0.1)', drawBorder: false },
                        ticks: {
                            callback: function(value) { return value + '%'; },
                            font: { size: 12 },
                            color: '#666'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12 },
                            color: '#666',
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                hover: { mode: 'index', intersect: false }
            };
        }

        window.IndexPage.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: config.deptStats.labels,
                datasets: [{
                    label: 'Tỷ lệ hoàn thành (%)',
                    data: config.deptStats.data,
                    backgroundColor: config.deptStats.colors,
                    borderRadius: isMobile ? 4 : 8,
                    maxBarThickness: isMobile ? 16 : 50,
                    borderColor: 'rgba(255, 255, 255, 0.8)',
                    borderWidth: isMobile ? 1 : 2
                }]
            },
            options: chartOptions,
            plugins: isMobile ? [mobileBarLabelsPlugin] : []
        });

        return window.IndexPage.chart;
    }

    window.IndexPage = window.IndexPage || {};
    window.IndexPage.initChart = initChart;

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

    document.addEventListener('DOMContentLoaded', function() {
        initSelectAllCheckbox();
        initRowHighlight();
        initDragToScroll();
        initCheckboxScroll();
        var isMobile = window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) {
            initChart();
        }
    });

    window.addEventListener('resize', function() {
        var isMobile = window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile && (!window.IndexPage || !window.IndexPage.chart || window.IndexPage.chartIsMobile)) {
            initChart();
        }
    });

})();
