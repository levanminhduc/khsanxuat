/**
 * Tailwind chi dung cho 5 modal cua indexdept.php (xem docs/requirements/indexdept-modals-tailwind/design.md).
 * Cac trang khac cua du an van la CSS thuan — dung them file vao `content` neu chua co quyet dinh moi.
 *
 * Build: tools\tailwind\build.cmd  (xem tools/tailwind/README.md)
 */
module.exports = {
    // Prefix tw- de khong va cham class legacy (.modal, .close, .hidden... trong style.css/style2.css).
    prefix: 'tw-',
    corePlugins: {
        // Preflight se reset toan trang, dap vo giao dien cac phan CSS thuan.
        preflight: false
    },
    content: [
        './indexdept.php',
        './views/indexdept/partials/*.php',
        './assets/js/indexdept/*.js'
    ],
    theme: {
        extend: {
            boxShadow: {
                'modal-soft': '0 26px 80px rgba(15, 23, 42, 0.28)'
            }
        }
    }
};
