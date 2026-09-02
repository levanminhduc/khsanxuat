# Tailwind cho modal indexdept

Tailwind chi phu trach 5 modal cua `indexdept.php`. Phan con lai cua du an van
la CSS thuan (BEM) — xem `docs/code-standards.md`.

Khong dung npm/node_modules: build bang **standalone CLI** (1 file .exe).

## Cai dat CLI

`tools/tailwind/tailwindcss.exe` bi gitignore (40MB). Sau khi clone, tai lai:

1. Vao https://github.com/tailwindlabs/tailwindcss/releases/tag/v3.4.17
2. Tai `tailwindcss-windows-x64.exe`
3. Doi ten thanh `tailwindcss.exe`, dat vao `tools/tailwind/`

**Phai la v3.x.** Toan bo class hien co viet theo cu phap v3
(`tw-bg-slate-950/55`, `tw-z-[10000]`, `sm:tw-inline-flex`); v4 doi cu phap
prefix thanh `tw:bg-white` nen se hong hang loat.

## Build

```
tools\tailwind\build.cmd          REM build 1 lan, minify
tools\tailwind\build.cmd watch    REM build lai moi khi sua file nguon
```

Dau ra: `assets/css/tailwind.css` (co commit vao git, de deploy khong can CLI).

Chay lai va **commit ca file dau ra** moi khi them/sua class `tw-*` trong
`indexdept.php`, `views/indexdept/partials/*.php` hoac `assets/js/indexdept/*.js`.
Ba duong dan nay la muc `content` trong `tailwind.config.js`; class `tw-*` viet
o file ngoai danh sach do se KHONG duoc sinh ra.

## Cau hinh

Xem `tailwind.config.js`:

- `prefix: 'tw-'` — tranh va cham class legacy (`.modal`, `.close`, `.hidden`
  trong `style.css` / `style2.css`).
- `preflight: false` — preflight reset toan trang, se dap vo cac trang CSS thuan.
- `boxShadow.modal-soft` — do bong dung chung cua 5 modal.
