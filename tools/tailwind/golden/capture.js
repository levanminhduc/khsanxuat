// Golden master: dump computed style cua moi node trong <body>.
// Chay qua Chrome DevTools MCP `evaluate_script` (hoac DevTools console) tren trang THAT,
// truyen danh sach id modal can ep mo de moi node deu co layout:
//   (ids) => { ...noi dung file nay... }   voi ids = ['deadlineModal', ...]
// Ket qua JSON luu thanh <ten>-<viewport>.json, so bang compare.cjs.
// Quy trinh day du: docs/tailwind-porting-guide.md
(ids) => {
  const MODALS = ids || [];

  // Ep mo modal (display:block) roi khoi phuc; ca baseline lan ban moi PHAI cung ep,
  // neu khong se lech gia (display root, min-width/min-height cua .modal-content).
  const saved = MODALS.map(id => {
    const el = document.getElementById(id);
    if (!el) return [id, null];
    const s = el.getAttribute('style');
    el.style.display = 'block';
    return [id, s];
  });

  // Khoa theo vi tri con trong cay, khong theo class (class doi khi port)
  const path = el => {
    const parts = [];
    let n = el;
    while (n && n.nodeType === 1 && parts.length < 14) {
      const p = n.parentElement;
      const i = p ? Array.prototype.indexOf.call(p.children, n) : 0;
      parts.unshift(n.tagName.toLowerCase() + ':' + i);
      n = p;
    }
    return parts.join('>');
  };

  const out = {};
  const labels = {};
  document.querySelectorAll('body *').forEach(el => {
    const p = path(el);
    const cs = getComputedStyle(el);
    // cssText cua computed style luon rong trong Chrome -> duyet tung property
    const d = [];
    for (let k = 0; k < cs.length; k++) { const n = cs[k]; d.push(n + ':' + cs.getPropertyValue(n)); }
    out[p] = d.join(';');
    labels[p] = el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') +
      (typeof el.className === 'string' && el.className ? '.' + el.className.trim().split(/\s+/).join('.') : '');
  });

  saved.forEach(([id, s]) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (s === null) el.removeAttribute('style'); else el.setAttribute('style', s);
  });

  return { viewport: window.innerWidth, elements: Object.keys(out).length, labels, styles: out };
}
