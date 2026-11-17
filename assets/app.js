(function () {
  var t = document.getElementById('toggleSidebar');
  var s = document.getElementById('sidebar');
  if (t && s) {
    t.addEventListener('click', function () { s.classList.toggle('open'); });
  }

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
