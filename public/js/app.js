/* PAWLUNE CatCare — JS ringan (tanpa framework) */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  window.fmtRp = (n) => 'Rp ' + Math.round(Number(n) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  // Sidebar (mobile)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-menu]');
    if (btn) { $('.sidebar').classList.toggle('open'); return; }
    if (!e.target.closest('.sidebar') && $('.sidebar.open')) $('.sidebar').classList.remove('open');
  });

  // Modal <dialog>
  document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-modal-open]');
    if (open) {
      const d = $(open.getAttribute('data-modal-open'));
      if (d) {
        // isi data dinamis: data-fill-*="nilai" -> elemen [data-fill="*"] / input[name=*] dalam modal
        Object.keys(open.dataset).forEach((k) => {
          if (k.indexOf('fill') !== 0 || k === 'fill') return;
          const key = k.slice(4).replace(/^[A-Z]/, (c) => c.toLowerCase()).replace(/([A-Z])/g, '_$1').toLowerCase();
          const val = open.dataset[k];
          $$('[data-fill="' + key + '"]', d).forEach((el) => (el.textContent = val));
          $$('[name="' + key + '"]', d).forEach((el) => { if (el.type === 'hidden') return; if (el.type === 'checkbox') el.checked = val === '1'; else el.value = val; });
        });
        if (open.dataset.action) { const f = $('form', d); if (f) f.setAttribute('action', open.dataset.action); }
        d.showModal();
      }
      return;
    }
    if (e.target.closest('[data-modal-close]')) { const d = e.target.closest('dialog'); if (d) d.close(); return; }
    if (e.target.tagName === 'DIALOG') {
      const r = e.target.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) e.target.close();
    }
  });
  $$('dialog[data-auto-open]').forEach((d) => d.showModal());

  // Konfirmasi submit
  document.addEventListener('submit', (e) => {
    const m = e.target.getAttribute('data-confirm');
    if (m && !window.confirm(m)) e.preventDefault();
  });

  // Tombol kembali: pakai riwayat jika ada (halaman dibuka di tab yang sama), kalau tidak ke href (dashboard)
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-back]');
    if (b && window.history.length > 1 && document.referrer.indexOf(location.origin) === 0) { e.preventDefault(); window.history.back(); }
  });

  // Cetak
  document.addEventListener('click', (e) => { if (e.target.closest('[data-print]')) window.print(); });

  // Baris dinamis (resep / layanan)
  document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-add-row]');
    if (add) {
      const tpl = $(add.getAttribute('data-add-row'));
      const box = $(add.getAttribute('data-target'));
      if (!tpl || !box) return;
      const idx = Date.now().toString(36) + Math.floor(Math.random() * 1000);
      const wrap = document.createElement('div');
      wrap.innerHTML = tpl.innerHTML.replace(/__i__/g, idx).trim();
      const row = wrap.firstElementChild;
      box.appendChild(row);
      box.dispatchEvent(new CustomEvent('rows:changed', { bubbles: true }));
      return;
    }
    const rm = e.target.closest('[data-remove-row]');
    if (rm) {
      const row = rm.closest('[data-row]');
      const box = row.parentElement;
      row.remove();
      box.dispatchEvent(new CustomEvent('rows:changed', { bubbles: true }));
    }
  });

  // Toast + bunyi notifikasi
  let audio;
  window.pawBeep = function () {
    try {
      audio = audio || new (window.AudioContext || window.webkitAudioContext)();
      [660, 880, 1040].forEach((f, i) => {
        const o = audio.createOscillator(), g = audio.createGain();
        o.type = 'square'; o.frequency.value = f; g.gain.value = 0.05;
        o.connect(g); g.connect(audio.destination);
        o.start(audio.currentTime + i * 0.16); o.stop(audio.currentTime + i * 0.16 + 0.13);
      });
    } catch (e) { /* abaikan jika browser memblokir audio */ }
  };
  window.pawToast = function (n, ttl) {
    const box = $('.toasts'); if (!box) return;
    const t = document.createElement('div');
    t.className = 'toast ' + (n.type || '');
    const b = document.createElement('b'); b.textContent = n.title || '';
    t.appendChild(b);
    if (n.body) { const p = document.createElement('div'); p.textContent = n.body; t.appendChild(p); }
    if (n.url) { const a = document.createElement('a'); a.href = n.url; a.textContent = 'Lihat →'; a.style.fontWeight = '800'; t.appendChild(a); }
    t.addEventListener('click', () => t.remove());
    box.appendChild(t);
    setTimeout(() => t.remove(), ttl || 12000);
  };

  // Flash server -> hilang otomatis
  $$('.flash.ok').forEach((f) => setTimeout(() => { f.style.transition = 'opacity .5s'; f.style.opacity = '0'; setTimeout(() => f.remove(), 600); }, 7000));
})();
