# SPEC — Dọn dead code & trùng lặp indexdept (JS + CSS)

**Trạng thái: HOÀN THÀNH (2026-09-03).** Toàn bộ mục A–D đã vào `main`. Hai file gốc trong spec
không còn tồn tại ở dạng cũ: `indexdept.js` đã tách thành 5 file theo tính năng, `indexdept.css`
đã tách phần modal ra `indexdept-modals.css` rồi port 5 modal sang class `tw-*`. Giữ file này làm
hồ sơ đối chiếu; việc còn lại xem mục "Tồn đọng".

## Mục tiêu ban đầu

Dọn dead code và code trùng lặp của trang `indexdept.php`, **chỉ dọn, không đổi hành vi**.
Các quyết định đã chốt (phỏng vấn 2026-09-02):

1. Khối check tiêu chí 131 (dept `kho`) giữ nguyên toàn bộ, kể cả comment code bên trong.
2. `getDeptDisplayName()` trong JS thay bằng chuỗi `deptDisplayName` từ `window.INDEXDEPT_BOOTSTRAP`.
3. CSS chỉ gộp các khối trùng HỆT nhau, không gộp khối "gần giống".
4. Gom boilerplate XHR và sửa XHR đồng bộ để đợt sau.

## Kết quả theo từng mục

| Mục | Nội dung | Trạng thái | Commit / vị trí hiện tại |
|---|---|---|---|
| A1–A9 | Xoá `applyDefaultSetting`, `applyAllDefaultSettings`, stub `autoSaveScore`/`showAutoSaveToast`, listener rỗng, biến không dùng, comment mồ côi | Xong | `8161ede` (−160 dòng) |
| B | `deptDisplayName` vào bootstrap, JS đọc từ bootstrap | Xong | `indexdept.php:223`, `assets/js/indexdept/indexdept-staff.js:25` |
| C1–C5 | Xoá `.diem-select`, `.nguoi-thuchien-select`, `select[name^="nguoi_thuchien_"]` (+ bản @media), `.system-message*` | Xong | `e820c77` |
| D1 | Gộp 2 khối `.btn-upload-image` | Xong | `indexdept.css:222` (1 khối) |
| D2 | Gộp 2 khối `.btn-default-setting` | Xong | `indexdept.css:205` (1 khối; dòng 338 là rule `font-family: inherit` dùng chung, không phải trùng) |
| D3 | Gộp `.image-count-badge` + `.file-count-badge` | Xong | `indexdept.css:252` |
| D4 | Gộp 3 `__footer-meta` | Hết ý nghĩa | Sau port Tailwind, `default-settings-modal__footer-meta` không còn rule; 2 selector còn lại ở `indexdept-modals.css:74–86` chỉ là hook JS (`--dirty`, `--active`), giữ riêng |

Khối 131 (`kho`) vẫn nguyên tại `assets/js/indexdept/indexdept-evaluation.js:210` (quyết định 1).

## Hậu kiểm đã chạy (2026-09-03)

- Grep: `applyDefaultSetting`, `applyAllDefaultSettings`, `getDeptDisplayName` (JS), `diem-select`,
  `nguoi-thuchien-select`, `system-message` → 0 kết quả trong `assets/`.
- `php -l` sạch; 29 handler `onclick=` trong partial đều có `window.<fn> =` tương ứng.
- Chrome DevTools trên `indexdept.php?dept=kehoach&id=8576`: console không lỗi (ngoài 404 favicon
  của host), mọi CSS/JS 200/304, mở/đóng đủ 5 modal ở 1004px và ~500px không exception.

## Tồn đọng (ngoài phạm vi đợt này, chưa làm)

- Gom boilerplate XHR: hiện 13 chỗ `xhr.open(` trong `assets/js/indexdept/*.js`.
- XHR đồng bộ duy nhất còn lại: `indexdept-evaluation.js:290` (`xhr.open('GET', …, false)` trong
  check ảnh khi submit) → chuyển async.
- Gộp các khối CSS "gần giống" của modal: không còn cần, vì 5 modal đã port sang `tw-*`.
- 31 dòng export `typeof x === 'function'`: đã thay bằng `window.<fn> = <fn>` khi tách file
  (`f2f2c13`), không còn tồn đọng.

## Tài liệu liên quan

- `docs/tailwind-porting-guide.md` — quy trình tách partial/JS + port Tailwind áp dụng cho trang khác.
- `tools/tailwind/README.md`, `tools/tailwind/golden/` — build + golden master.
