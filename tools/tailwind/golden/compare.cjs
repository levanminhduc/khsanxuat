// So 2 ban dump cua capture.js:  node compare.cjs <base.json> <cur.json>
//   MAX=999 PROPS=80  : in nhieu element/prop hon (mac dinh 30/16)
//   GEOM=0            : bo qua width/height/top/left... (chi so style, khong so hinh hoc)
// Exit code 1 khi con khac biet. Quy trinh day du: docs/tailwind-porting-guide.md
const fs = require('fs');
const [, , A_FILE, B_FILE] = process.argv;
if (!A_FILE || !B_FILE) { console.error('usage: node compare.cjs <base.json> <cur.json>'); process.exit(2); }
const MAX = +(process.env.MAX || 30);
const PROPS = +(process.env.PROPS || 16);
const GEOM = process.env.GEOM !== '0';

function toMap(t) {
  const m = {};
  t.split(/;(?=[a-zA-Z-]+:)/).forEach(d => { const i = d.indexOf(':'); if (i > 0) m[d.slice(0, i)] = d.slice(i + 1); });
  return m;
}

// Khac biet da xac minh la vo hai:
// - transition-* lap lai cung gia tri ("0s, 0s" vs "0s") la tuong duong
// - bong trong suot 0px (ring rong cua Tailwind) khong ve gi
// - bien --tw-* chi la nguyen lieu; neu doi ket qua thi thuoc tinh that se lech va van bi bat
// - alias logical (inline-size, border-block...) trung voi thuoc tinh vat ly da so
const dedupe = v => [...new Set(String(v).split(/,\s*/))].join(', ');
const noEmptyShadow = v => String(v).split(/,\s*(?=rgb)/).filter(x => !/^rgba\(0, 0, 0, 0\) 0px 0px 0px 0px$/.test(x)).join(', ');
const GEOM_RE = /^(width|height|top|left|right|bottom|inline-size|block-size|perspective-origin|transform-origin|inset-.*|(min|max)-(inline|block)-size)$/;
const LOGICAL_RE = /border-(block|inline|end|start)|padding-(block|inline)|margin-(block|inline)/;
const IGNORE = (p, a, b) =>
  (p === 'box-shadow' && noEmptyShadow(a) === noEmptyShadow(b)) ||
  (p === 'transition-property' && String(a).replace('-webkit-text-decoration-color, ', '') === String(b).replace('-webkit-text-decoration-color, ', '')) ||
  p.startsWith('--tw-') ||
  (/^transition-(delay|duration|timing-function|behavior)$/.test(p) && dedupe(a) === dedupe(b)) ||
  LOGICAL_RE.test(p) ||
  (!GEOM && GEOM_RE.test(p));

const A = JSON.parse(fs.readFileSync(A_FILE, 'utf8'));
const B = JSON.parse(fs.readFileSync(B_FILE, 'utf8'));
console.log('viewport ' + A.viewport + '  base ' + A.elements + ' el  ->  cur ' + B.elements + ' el');

const ka = Object.keys(A.styles), kb = new Set(Object.keys(B.styles));
const gone = ka.filter(k => !kb.has(k));
if (gone.length) console.log('MAT ' + gone.length + ' element: ' + gone.slice(0, 5).map(k => (A.labels[k] || k).slice(0, 70)).join(' | '));

let total = 0, ignored = 0;
const perEl = [];
for (const k of ka) {
  if (!kb.has(k) || A.styles[k] === B.styles[k]) continue;
  const ma = toMap(A.styles[k]), mb = toMap(B.styles[k]);
  const ds = [];
  for (const p of new Set([...Object.keys(ma), ...Object.keys(mb)])) {
    if (ma[p] === mb[p]) continue;
    // dang chay animation blink -> opacity lay mau lech, khong phai hoi quy
    const flake = p === 'opacity' && (ma['animation-name'] || '').includes('blink');
    // flex-direction vo tac dung khi display la grid
    const inert = p === 'flex-direction' && ma['display'] === 'grid' && mb['display'] === 'grid';
    if (IGNORE(p, ma[p], mb[p]) || flake || inert) { ignored++; continue; }
    ds.push([p, ma[p], mb[p]]);
  }
  if (ds.length) perEl.push([k, ds, A.labels[k] || k]);
}
total = perEl.reduce((s, x) => s + x[1].length, 0);
console.log('element khac: ' + perEl.length + ' | declaration khac: ' + total + ' | bo qua ' + ignored);

perEl.slice(0, MAX).forEach(([k, ds, label]) => {
  console.log('\n  ' + label.slice(0, 160) + '\n    path: ' + k.slice(-70));
  ds.slice(0, PROPS).forEach(([p, a, b]) => console.log('      ' + p + ':  "' + a + '"  ->  "' + b + '"'));
  if (ds.length > PROPS) console.log('      ... +' + (ds.length - PROPS) + ' prop');
});
if (perEl.length > MAX) console.log('\n  ... con ' + (perEl.length - MAX) + ' element nua');
console.log('\n=====> ' + (total === 0 ? 'GOLDEN OK' : 'CON ' + total + ' KHAC BIET'));
process.exit(total === 0 ? 0 : 1);
