(function () {
  'use strict';
  var MODE_KEY = 'raport_mode';
  var active = null;
  var end = null;
  var dragging = false;
  var dirty = false;
  var dimsCache = null;

  function grid() { return document.querySelector('table.tbl-grid'); }

  function dims() {
    if (dimsCache) return dimsCache;
    var t = grid();
    if (!t) return { r: 0, c: 0 };
    var d = { r: 0, c: 0 };
    t.querySelectorAll('td[data-r][data-c]').forEach(function (td) {
      var r = +td.dataset.r, c = +td.dataset.c;
      if (r + 1 > d.r) d.r = r + 1;
      if (c + 1 > d.c) d.c = c + 1;
    });
    dimsCache = d;
    return d;
  }

  function cellAt(r, c) {
    var t = grid();
    return t ? t.querySelector('td[data-r="' + r + '"][data-c="' + c + '"]') : null;
  }

  function inputAt(r, c) {
    var td = cellAt(r, c);
    return td ? td.querySelector('input') : null;
  }

  function rect() {
    if (!active) return null;
    var e = end || active;
    return {
      r1: Math.min(active.r, e.r), r2: Math.max(active.r, e.r),
      c1: Math.min(active.c, e.c), c2: Math.max(active.c, e.c)
    };
  }

  function paint() {
    var t = grid();
    if (!t) return;
    t.querySelectorAll('td.is-sel,td.is-active').forEach(function (td) {
      td.classList.remove('is-sel');
      td.classList.remove('is-active');
    });
    var R = rect();
    if (!R) return;
    for (var r = R.r1; r <= R.r2; r++) {
      for (var c = R.c1; c <= R.c2; c++) {
        var td = cellAt(r, c);
        if (td) td.classList.add('is-sel');
      }
    }
    var a = cellAt(active.r, active.c);
    if (a) {
      a.classList.add('is-sel');
      a.classList.add('is-active');
    }
  }

  function setActive(r, c, focusInput, selectText) {
    var d = dims();
    if (!d.r || !d.c) return;
    r = Math.max(0, Math.min(d.r - 1, r));
    c = Math.max(0, Math.min(d.c - 1, c));
    active = { r: r, c: c };
    end = { r: r, c: c };
    paint();
    if (focusInput) {
      var i = inputAt(r, c);
      if (i) {
        try { i.focus({ preventScroll: true }); } catch (e) { i.focus(); }
        try { i.scrollIntoView({ block: 'nearest', inline: 'nearest' }); } catch (e2) {}
        if (selectText) i.select();
      }
    }
  }

  function extendTo(r, c) {
    var d = dims();
    if (!active || !d.r) return;
    end = {
      r: Math.max(0, Math.min(d.r - 1, r)),
      c: Math.max(0, Math.min(d.c - 1, c))
    };
    paint();
  }

  function parseNum(v) {
    var s = String(v == null ? '' : v).trim().replace(',', '.');
    if (s === '') return null;
    var n = parseFloat(s);
    return isFinite(n) ? n : null;
  }

  function fmtNum(v) {
    return v % 1 === 0 ? String(Math.round(v)) : String(Math.round(v * 10) / 10);
  }

  function normalize(raw) {
    if (raw == null) return null;
    var s = String(raw).trim().replace(/\s+/g, '').replace(',', '.');
    if (s === '') return '';
    if (!/^-?\d+(\.\d+)?$/.test(s)) return null;
    var v = parseFloat(s);
    if (v < 0) v = 0;
    if (v > 100) v = 100;
    return fmtNum(Math.round(v * 10) / 10);
  }

  function setCellValue(r, c, val) {
    var i = inputAt(r, c);
    if (!i || val === null) return false;
    if (i.value === val) return false;
    i.value = val;
    return true;
  }

  function updateAvgRow(r) {
    var td = cellAt(r, 0);
    if (!td) return;
    var tr = td.closest('tr');
    if (!tr) return;
    var out = tr.querySelector('[data-avg]');
    if (!out) return;
    var groups = {};
    tr.querySelectorAll('td[data-r][data-c]').forEach(function (cell) {
      var i = cell.querySelector('input');
      if (!i) return;
      var v = parseNum(i.value);
      if (v === null) return;
      var g = cell.getAttribute('data-group') || '';
      if (!groups[g]) groups[g] = { sum: 0, n: 0 };
      groups[g].sum += v;
      groups[g].n++;
    });
    var avgs = Object.keys(groups).map(function (k) { return groups[k].sum / groups[k].n; });
    var val = avgs.length ? avgs.reduce(function (a, b) { return a + b; }, 0) / avgs.length : null;
    var b = out.querySelector('b') || out;
    b.textContent = val === null ? '-' : fmtNum(Math.round(val * 10) / 10);
  }

  function markDirty() {
    if (dirty) return;
    dirty = true;
    var s = document.getElementById('saveStatus');
    if (s) s.hidden = false;
  }

  function clearDirty() {
    dirty = false;
    var s = document.getElementById('saveStatus');
    if (s) s.hidden = true;
  }

  function applyMode(m) {
    dimsCache = null;
    if (m !== 'grid' && m !== 'tabel') m = 'tabel';
    document.querySelectorAll('table[data-grid]').forEach(function (t) {
      t.classList.toggle('tbl-grid', m === 'grid');
      var w = t.closest('.table-wrap');
      if (w) w.classList.toggle('grid-wrap', m === 'grid');
      if (m !== 'grid') t.classList.remove('dragging');
    });
    document.querySelectorAll('#modeSwitch .seg-btn').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-mode') === m);
    });
    try { localStorage.setItem(MODE_KEY, m); } catch (e) {}
    if (m !== 'grid') {
      active = null;
      end = null;
      dragging = false;
      if (grid() === null) {
        document.querySelectorAll('td.is-sel,td.is-active').forEach(function (td) {
          td.classList.remove('is-sel');
          td.classList.remove('is-active');
        });
      }
    }
  }

  function moveTab(shift) {
    var d = dims();
    if (!active || !d.r) return;
    var r = active.r, c = active.c + (shift ? -1 : 1);
    if (c >= d.c) { c = 0; r++; }
    else if (c < 0) { c = d.c - 1; r--; }
    if (r >= d.r) { r = 0; c = 0; }
    if (r < 0) { r = d.r - 1; c = d.c - 1; }
    setActive(r, c, true, true);
  }

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest && ev.target.closest('#modeSwitch .seg-btn');
    if (!b) return;
    applyMode(b.getAttribute('data-mode'));
  });

  document.addEventListener('mousedown', function (ev) {
    var t = grid();
    if (!t) return;
    var td = ev.target.closest && ev.target.closest('td[data-r][data-c]');
    if (td && t.contains(td)) {
      var r = +td.dataset.r, c = +td.dataset.c;
      if (ev.shiftKey && active) {
        extendTo(r, c);
        ev.preventDefault();
      } else {
        setActive(r, c, false, false);
        dragging = true;
        t.classList.add('dragging');
      }
      return;
    }
    var th = ev.target.closest && ev.target.closest('th[data-col]');
    if (th && t.contains(th)) {
      var cc = +th.dataset.col;
      var d = dims();
      active = { r: 0, c: cc };
      end = { r: Math.max(0, d.r - 1), c: cc };
      paint();
      ev.preventDefault();
      return;
    }
    var rh = ev.target.closest && ev.target.closest('td[data-row-head]');
    if (rh && t.contains(rh)) {
      var rr = +rh.dataset.rowHead;
      var dd = dims();
      active = { r: rr, c: 0 };
      end = { r: rr, c: Math.max(0, dd.c - 1) };
      paint();
      ev.preventDefault();
    }
  });

  document.addEventListener('mouseover', function (ev) {
    if (!dragging) return;
    var td = ev.target.closest && ev.target.closest('td[data-r][data-c]');
    if (!td) return;
    extendTo(+td.dataset.r, +td.dataset.c);
  });

  document.addEventListener('mouseup', function () {
    dragging = false;
    var t = grid();
    if (t) t.classList.remove('dragging');
  });

  document.addEventListener('focusin', function (ev) {
    var el = ev.target;
    if (el && el.tagName === 'INPUT' && el.closest && el.closest('td[data-r][data-c]')) {
      el.dataset.orig = el.value;
    }
  });

  document.addEventListener('input', function (ev) {
    var el = ev.target;
    if (!el || el.tagName !== 'INPUT' || !el.name) return;
    if (el.name.indexOf('nilai[') !== 0 && el.name.indexOf('jilid[') !== 0) return;
    var cleaned = el.value.replace(/[^\d.,]/g, '');
    if (cleaned !== el.value) el.value = cleaned;
    markDirty();
    var td = el.closest && el.closest('td[data-r][data-c]');
    if (td) updateAvgRow(+td.dataset.r);
  });

  document.addEventListener('change', function (ev) {
    var el = ev.target;
    if (el && el.tagName === 'SELECT' && el.name && el.name.indexOf('jilid[') === 0) {
      markDirty();
    }
  });

  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.querySelector && f.querySelector('input[name^="nilai["]')) clearDirty();
  }, true);

  window.addEventListener('beforeunload', function (ev) {
    if (!dirty) return;
    ev.preventDefault();
    ev.returnValue = '';
  });

  document.addEventListener('keydown', function (ev) {
    var t = grid();
    if (!t) return;
    var el = ev.target;
    var inCell = !!(el && el.tagName === 'INPUT' && el.closest && el.closest('td[data-r][data-c]'));
    var inOther = !!(el && (el.tagName === 'SELECT' || el.tagName === 'TEXTAREA' ||
      (el.tagName === 'INPUT' && !inCell)));
    if ((ev.ctrlKey || ev.metaKey) && ev.key !== 'Home' && ev.key !== 'End') return;
    if (ev.altKey) return;
    var d = dims();
    if (!d.r) return;

    if (!active && (inCell || inOther === false)) {
      if (ev.key.length !== 1 && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight',
        'Home', 'End', 'Enter', 'Tab', 'Escape'].indexOf(ev.key) === -1) return;
      active = { r: 0, c: 0 };
      end = { r: 0, c: 0 };
      paint();
    }
    if (!active) return;

    var R = rect();

    if (ev.key === 'Escape' && inCell) {
      if (el.dataset.orig !== undefined && el.value !== el.dataset.orig) {
        el.value = el.dataset.orig;
        updateAvgRow(+el.closest('td[data-r][data-c]').dataset.r);
      }
      return;
    }

    if (ev.key === 'Delete' && R && (R.r2 > R.r1 || R.c2 > R.c1)) {
      ev.preventDefault();
      for (var rr = R.r1; rr <= R.r2; rr++) {
        for (var cc = R.c1; cc <= R.c2; cc++) {
          setCellValue(rr, cc, '');
          updateAvgRow(rr);
        }
      }
      markDirty();
      return;
    }

    if (inOther) return;

    var K = ev.key;
    if (K === 'ArrowLeft') {
      if (inCell && el.selectionStart > 0) return;
      ev.preventDefault();
      if (active.c > 0 || active.r > 0) {
        setActive(active.c > 0 ? active.r : active.r - 1,
          active.c > 0 ? active.c - 1 : d.c - 1, true, true);
      }
      return;
    }
    if (K === 'ArrowRight') {
      if (inCell && el.selectionEnd < el.value.length) return;
      ev.preventDefault();
      if (active.c < d.c - 1) setActive(active.r, active.c + 1, true, true);
      else if (active.r < d.r - 1) setActive(active.r + 1, 0, true, true);
      return;
    }
    if (K === 'ArrowUp') {
      ev.preventDefault();
      if (active.r > 0) setActive(active.r - 1, active.c, true, true);
      return;
    }
    if (K === 'ArrowDown' || (K === 'Enter' && !ev.shiftKey)) {
      ev.preventDefault();
      if (active.r < d.r - 1) setActive(active.r + 1, active.c, true, true);
      return;
    }
    if (K === 'Enter' && ev.shiftKey) {
      ev.preventDefault();
      if (active.r > 0) setActive(active.r - 1, active.c, true, true);
      return;
    }
    if (K === 'Tab') {
      ev.preventDefault();
      moveTab(ev.shiftKey);
      return;
    }
    if (K === 'Home') {
      ev.preventDefault();
      if (ev.ctrlKey || ev.metaKey) setActive(0, 0, true, true);
      else setActive(active.r, 0, true, true);
      return;
    }
    if (K === 'End') {
      ev.preventDefault();
      if (ev.ctrlKey || ev.metaKey) setActive(d.r - 1, d.c - 1, true, true);
      else setActive(active.r, d.c - 1, true, true);
      return;
    }

    if (!inCell && K.length === 1) {
      if (!/[\d.,]/.test(K)) return;
      ev.preventDefault();
      var i = inputAt(active.r, active.c);
      if (!i) return;
      try { i.focus({ preventScroll: true }); } catch (e) { i.focus(); }
      i.value = K;
      i.dataset.orig = K;
      updateAvgRow(active.r);
      markDirty();
    }
  });

  document.addEventListener('paste', function (ev) {
    var t = grid();
    if (!t) return;
    var el = ev.target;
    if (!el || el.tagName !== 'INPUT' || !el.closest || !el.closest('td[data-r][data-c]')) return;
    if (!active) return;
    var cd = ev.clipboardData || window.clipboardData;
    if (!cd) return;
    var text = cd.getData('text/plain') || '';
    if (text === '') return;
    ev.preventDefault();
    var rows = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
    while (rows.length && rows[rows.length - 1] === '') rows.pop();
    var block = rows.map(function (line) { return line.split('\t'); });
    var d = dims();
    var R = rect();
    var touched = {};
    var i, j;

    if (block.length === 1 && block[0].length === 1) {
      var satu = normalize(block[0][0]);
      var area = (R && (R.r2 > R.r1 || R.c2 > R.c1)) ? R : { r1: active.r, r2: active.r, c1: active.c, c2: active.c };
      for (i = area.r1; i <= area.r2; i++) {
        for (j = area.c1; j <= area.c2; j++) {
          if (satu !== null && setCellValue(i, j, satu)) touched[i] = true;
        }
      }
    } else {
      var maxC = 0;
      for (i = 0; i < block.length; i++) {
        if (block[i].length > maxC) maxC = block[i].length;
        for (j = 0; j < block[i].length; j++) {
          var rr = active.r + i, cc = active.c + j;
          if (rr >= d.r || cc >= d.c) continue;
          var val = normalize(block[i][j]);
          if (val === null) continue;
          if (setCellValue(rr, cc, val)) touched[rr] = true;
        }
      }
      end = {
        r: Math.min(d.r - 1, active.r + block.length - 1),
        c: Math.min(d.c - 1, active.c + maxC - 1)
      };
      paint();
    }

    Object.keys(touched).forEach(function (k) { updateAvgRow(+k); });
    if (Object.keys(touched).length) markDirty();
  });

  document.addEventListener('copy', function (ev) {
    var t = grid();
    if (!t) return;
    var R = rect();
    if (!R || !active) return;
    if (R.r2 === R.r1 && R.c2 === R.c1) return;
    var lines = [];
    for (var r = R.r1; r <= R.r2; r++) {
      var row = [];
      for (var c = R.c1; c <= R.c2; c++) {
        var i = inputAt(r, c);
        row.push(i ? i.value : '');
      }
      lines.push(row.join('\t'));
    }
    ev.preventDefault();
    ev.clipboardData.setData('text/plain', lines.join('\n'));
  });

  var m = 'tabel';
  try { m = localStorage.getItem(MODE_KEY) || 'tabel'; } catch (e) {}
  applyMode(m);
})();
