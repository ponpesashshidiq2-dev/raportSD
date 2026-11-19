(function () {
  var t = document.getElementById('toggleSidebar');
  var s = document.getElementById('sidebar');
  var b = document.getElementById('sidebarBackdrop');
  function closeSidebar() {
    if (s) s.classList.remove('open');
    if (b) b.classList.remove('show');
  }
  if (t && s) {
    t.addEventListener('click', function () {
      s.classList.toggle('open');
      if (b) b.classList.toggle('show', s.classList.contains('open'));
    });
  }
  if (b) {
    b.addEventListener('click', closeSidebar);
  }
  if (s) {
    s.addEventListener('click', function (ev) {
      if (ev.target.closest('a')) closeSidebar();
    });
  }
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') closeSidebar();
  });

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.tagName !== 'FORM' || !f.hasAttribute('data-confirm')) return;
    var msg = f.getAttribute('data-confirm') || 'Lanjutkan?';
    if (!window.confirm(msg)) ev.preventDefault();
  });

  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('a[data-confirm]');
    if (a && !window.confirm(a.getAttribute('data-confirm') || 'Lanjutkan?')) ev.preventDefault();
  });

  var printBtns = document.querySelectorAll('.js-print, #printBtn');
  printBtns.forEach(function (btn) {
    btn.addEventListener('click', function () { window.print(); });
  });

  document.querySelectorAll('[data-select-all]').forEach(function (box) {
    box.addEventListener('change', function () {
      var name = box.getAttribute('data-select-all');
      document.querySelectorAll('input[name="' + name + '[]"]').forEach(function (c) {
        c.checked = box.checked;
      });
    });
  });
})();
