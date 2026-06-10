// Tab switching cho trang Quản lý biểu mẫu.
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
            tabContents.forEach(function (c) { c.classList.remove('active'); });
            document.getElementById('tab-' + this.getAttribute('data-tab')).classList.add('active');
        });
    });
});
