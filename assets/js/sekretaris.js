(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  // Menu mobile
  $$('[data-menu]').forEach(b => b.addEventListener('click', () => document.body.classList.toggle('menu-open')));

  // Tutup notifikasi / toast
  $$('[data-dismiss]').forEach(b => b.addEventListener('click', () => b.closest(b.dataset.dismiss)?.remove()));

  // Nama berkas + drag & drop
  const input = $('#berkas'), label = $('#fileName'), drop = $('#drop');
  if (input) {
    const show = () => { if (input.files[0]) label.textContent = input.files[0].name; };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(); } });
    drop.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    drop.tabIndex = 0;
  }

  // Modal konfirmasi "Apakah Anda yakin?"
  const mc = $('#mConfirm');
  let pending = null;
  const close = () => { mc.hidden = true; pending = null; };
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    e.preventDefault();
    if (!f.checkValidity()) { f.reportValidity(); return; }
    pending = f;
    $('[data-text]', mc).textContent = f.dataset.confirm;
    $$('[data-ic]', mc).forEach(x => { x.hidden = x.dataset.ic !== (f.dataset.icon || 'alert'); });
    mc.hidden = false;
    $('[data-yes]', mc).focus();
  }));
  $('[data-no]', mc).addEventListener('click', close);
  $('[data-yes]', mc).addEventListener('click', () => { const f = pending; close(); f && f.submit(); });
  mc.addEventListener('click', e => { if (e.target === mc) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !mc.hidden) close(); });

  // Modal sukses -> lanjut ke halaman berikutnya
  const ok = $('#mOk');
  if (ok && ok.hasAttribute('data-show')) {
    ok.hidden = false;
    setTimeout(() => { location.href = ok.dataset.next; }, +ok.dataset.delay || 1800);
  }
})();
