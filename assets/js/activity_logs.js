document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('filter-form');
    if (!form) {
        return;
    }

    // Doi select => submit ngay. Form khong co input `page` nen moi lan loc deu ve trang 1.
    form.querySelectorAll('select').forEach(function (el) {
        el.addEventListener('change', function () {
            form.submit();
        });
    });
});
