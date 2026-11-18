<?php
$p = profil();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan' && can_edit()) {
        $taOto = (int) (user()['angkatan'] ?? 0) > 0;
        $konteks = [
            'semester' => in_array((int) ($_POST['semester'] ?? 1), [1, 2], true) ? (int) $_POST['semester'] : 1,
            'kelas_aktif' => min(6, max(1, (int) ($_POST['kelas_aktif'] ?? 1))),
            'fase' => in_array($_POST['fase'] ?? '', ['A', 'B', 'C'], true) ? $_POST['fase'] : 'A',
        ];
        if (!$taOto) {
            $konteks['ta'] = trim($_POST['ta'] ?? '');
            if (!preg_match('/^\d{4}\/\d{4}$/', $konteks['ta'])) {
                flash_set('error', 'Format tahun ajaran harus 2025/2026.');
                redirect('profil');
            }
        }
        if (is_admin()) {
            foreach (['sekolah', 'npsn', 'alamat', 'kota', 'telp', 'email', 'website', 'kepsek', 'kepsek_nip'] as $f) {
                if (isset($_POST[$f])) {
                    $konteks[$f] = trim($_POST[$f]);
                }
            }
        }
        $c = ctx();
        $tglR = trim($_POST['tanggal_rapor'] ?? '');
        $tglB = trim($_POST['tanggal_biodata'] ?? '');
        $tglR = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglR) ? $tglR : '';
        $tglB = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglB) ? $tglB : '';
        $lamaR = tanggal_rapor_ctx((int) $c['kelas'], (int) $c['semester']);
        $lamaB = tanggal_biodata_ctx((int) $c['kelas'], (int) $c['semester']);
        update_row('profil', $konteks, 'id=?', [1]);
        $nMutasi = (int) $c['kelas'] !== (int) $konteks['kelas_aktif'] ? mutasi_kelas_siswa((int) $konteks['kelas_aktif']) : 0;
        if ($tglR !== $lamaR || $tglB !== $lamaB) {
            tanggal_ctx_set((int) $konteks['kelas_aktif'], (int) $konteks['semester'], $tglR, $tglB);
        }
        flash_set('success', 'Profil dan konteks raport tersimpan.'
            . ($nMutasi > 0 ? ' ' . $nMutasi . ' siswa aktif otomatis dipindahkan ke Kelas ' . (int) $konteks['kelas_aktif'] . '.' : ''));
        redirect('profil');
    }

    if ($aksi === 'logo' && is_admin()) {
        $f = $_FILES['logo'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash_set('error', 'File logo belum dipilih atau gagal diunggah.');
            redirect('profil');
        }
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true)) {
            flash_set('error', 'Format logo harus PNG, JPG, JPEG, WEBP, GIF, atau SVG.');
            redirect('profil');
        }
        if ((int) $f['size'] > 2 * 1024 * 1024) {
            flash_set('error', 'Ukuran logo maksimal 2 MB.');
            redirect('profil');
        }
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0777, true);
        }
        $namaFile = 'logo.' . $ext;
        $lama = basename(trim((string) profil()['logo']));
        if ($lama !== '' && $lama !== $namaFile) {
            @unlink(DATA_DIR . '/' . $lama);
        }
        if (!move_uploaded_file($f['tmp_name'], DATA_DIR . '/' . $namaFile)) {
            flash_set('error', 'Gagal menyimpan file logo.');
            redirect('profil');
        }
        update_row('profil', ['logo' => $namaFile], 'id=?', [1]);
        flash_set('success', 'Logo sampul berhasil diunggah.');
        redirect('profil');
    }

    if ($aksi === 'logo_hapus' && is_admin()) {
        $lama = basename(trim((string) profil()['logo']));
        if ($lama !== '') {
            @unlink(DATA_DIR . '/' . $lama);
        }
        update_row('profil', ['logo' => ''], 'id=?', [1]);
        flash_set('success', 'Logo sampul dihapus.');
        redirect('profil');
    }

    if ($aksi === 'walikelas' && can_edit()) {
        for ($s = 1; $s <= 2; $s++) {
            for ($k = 1; $k <= 6; $k++) {
                $nama = trim($_POST['wk_nama_' . $k . '_' . $s] ?? '');
                $nip = trim($_POST['wk_nip_' . $k . '_' . $s] ?? '');
                if (one('SELECT kelas FROM walikelas WHERE kelas=? AND semester=?', [$k, $s])) {
                    update_row('walikelas', ['nama' => $nama, 'nip' => $nip], 'kelas=? AND semester=?', [$k, $s]);
                } else {
                    insert_row('walikelas', ['kelas' => $k, 'semester' => $s, 'nama' => $nama, 'nip' => $nip]);
                }
            }
        }
        flash_set('success', 'Data wali kelas per kelas & semester tersimpan.');
        redirect('profil');
    }

    if ($aksi === 'nama_kelas' && can_edit()) {
        db()->beginTransaction();
        try {
            for ($k = 1; $k <= 6; $k++) {
                $nm = trim($_POST['nk_' . $k] ?? '');
                if (one('SELECT kelas FROM kelas_nama WHERE kelas=?', [$k])) {
                    update_row('kelas_nama', ['nama' => $nm], 'kelas=?', [$k]);
                } else {
                    insert_row('kelas_nama', ['kelas' => $k, 'nama' => $nm]);
                }
            }
            db()->commit();
        } catch (Throwable $exK) {
            db()->rollBack();
            flash_set('error', 'Gagal menyimpan nama kelas: ' . $exK->getMessage());
            redirect('profil');
        }
        flash_set('success', 'Nama kelas tersimpan — tampil pada kolom Kelas di cetak raport.');
        redirect('profil');
    }

}

$disabled = is_admin() ? '' : ' disabled';
$disabledEdit = can_edit() ? '' : ' disabled';
$uOto = user();
$taOto = (int) ($uOto['angkatan'] ?? 0) > 0;
$c = ctx();
page_head('Profil & Konteks Raport', 'Identitas sekolah, tahun ajaran, semester, kelas aktif, dan wali kelas.');
?>
<form method="post" action="index.php?page=profil">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">

  <div class="save-bar">
    <button class="btn" type="submit">Simpan Profil &amp; Konteks</button>
    <span class="hint">Tombol menempel di atas — simpan kapan saja tanpa menggulir ke bawah.</span>
  </div>

  <div class="grid grid-2">
    <div class="card">
      <h3>Identitas Sekolah</h3>
      <?php if (!is_admin()): ?><div class="alert alert-info">Hanya administrator yang dapat mengubah identitas sekolah.</div><?php endif; ?>
      <div class="field">
        <label>Nama Sekolah</label>
        <input type="text" name="sekolah" value="<?= e($p['sekolah']) ?>"<?= $disabled ?>>
      </div>
      <div class="form-row">
        <div class="field"><label>NPSN</label><input type="text" name="npsn" value="<?= e($p['npsn']) ?>"<?= $disabled ?>></div>
        <div class="field"><label>Kota</label><input type="text" name="kota" value="<?= e($p['kota']) ?>"<?= $disabled ?>></div>
      </div>
      <div class="field"><label>Alamat</label><textarea name="alamat" rows="2"<?= $disabled ?>><?= e($p['alamat']) ?></textarea></div>
      <div class="form-row">
        <div class="field"><label>Telepon</label><input type="text" name="telp" value="<?= e($p['telp']) ?>"<?= $disabled ?>></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($p['email']) ?>"<?= $disabled ?>></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Kepala Sekolah</label><input type="text" name="kepsek" value="<?= e($p['kepsek']) ?>"<?= $disabled ?>></div>
        <div class="field"><label>NBM Kepala Sekolah</label><input type="text" name="kepsek_nip" value="<?= e($p['kepsek_nip']) ?>"<?= $disabled ?>></div>
      </div>
    </div>

    <div class="card">
      <h3>Konteks Raport</h3>
      <div class="hint mb">Seluruh input nilai dan cetak raport mengikuti konteks di bawah ini. Ganti kelas juga bisa lewat dropdown <b>Kelas</b> di bar atas — data kelas sebelumnya tetap tersimpan dan bisa dibuka kembali kapan saja.</div>
      <div class="form-row">
        <div class="field">
          <label>Tahun Ajaran</label>
          <?php if ($taOto): ?>
            <input type="text" value="<?= e(ta_oto($p)) ?>" readonly style="background:#f1f5f9;color:var(--muted)">
            <div class="hint">Otomatis dari tahun angkatan Anda (<?= (int) $uOto['angkatan'] ?>) + Kelas Aktif — saat kelas konteks diganti, tahun ajaran ikut menyesuaikan.</div>
          <?php else: ?>
            <input type="text" name="ta" value="<?= e($p['ta']) ?>" placeholder="2025/2026" required>
          <?php endif; ?>
        </div>
        <div class="field">
          <label>Semester</label>
          <select name="semester">
            <option value="1"<?= (int) $p['semester'] === 1 ? ' selected' : '' ?>>Semester 1 (Ganjil)</option>
            <option value="2"<?= (int) $p['semester'] === 2 ? ' selected' : '' ?>>Semester 2 (Genap)</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Kelas Aktif</label>
          <select name="kelas_aktif" onchange="var f=this.value<=2?'A':(this.value<=4?'B':'C');var s=this.form.querySelector('select[name=fase]');if(s)s.value=f;">
            <?php for ($k = 1; $k <= 6; $k++): ?>
              <option value="<?= $k ?>"<?= (int) $p['kelas_aktif'] === $k ? ' selected' : '' ?>><?= e(kelas_label($k)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field">
          <label>Fase</label>
          <select name="fase">
            <?php foreach (['A', 'B', 'C'] as $f): ?>
              <option value="<?= $f ?>"<?= $p['fase'] === $f ? ' selected' : '' ?>><?= e(fase_label($f)) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="hint">Fase otomatis: kelas 1-2 = A, 3-4 = B, 5-6 = C.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Tanggal Rapor (untuk Cetak Raport)</label>
          <input type="date" name="tanggal_rapor" value="<?= e(tanggal_rapor_ctx()) ?>"<?= $disabledEdit ?>>
          <div class="hint">Tanggal yang tercetak pada raport — <b>tersimpan per Kelas &amp; Semester</b>. Ganti kelas/semester konteks, tanggal otomatis mengikuti yang tersimpan untuk konteks itu. Kosongkan untuk memakai tanggal hari ini.</div>
        </div>
        <div class="field">
          <label>Tanggal Biodata (untuk Sampul)</label>
          <input type="date" name="tanggal_biodata" value="<?= e(tanggal_biodata_ctx()) ?>"<?= $disabledEdit ?>>
          <div class="hint">Tanggal pada biodata siswa di sampul — <b>tersimpan per Kelas &amp; Semester</b>, ikut berubah saat konteks diganti. Kosongkan untuk memakai tanggal masuk tiap siswa.</div>
        </div>
      </div>
      <div class="hint mb">Nilai Akhir raport = rata-rata nilai sumatif (rata Lingkup Materi &amp; rata AES) — nilai formatif dipakai untuk deskripsi capaian kompetensi.</div>
    </div>
  </div>
</form>

<form method="post" action="index.php?page=profil">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="nama_kelas">
  <div class="card">
    <h3>Nama Kelas (untuk Cetak Raport)</h3>
    <?php if (!can_edit()): ?><div class="alert alert-warning">Hanya administrator dan wali kelas yang dapat mengubah data ini.</div><?php endif; ?>
    <p class="muted" style="font-size:12.5px;margin:0 0 10px">Nama kelas tampil pada kolom <b>Kelas</b> di cetak raport, mis. <b>III (Ustman Bin Affan)</b>. Biarkan kosong jika hanya ingin menampilkan angka romawi (mis. <b>III</b>). Tersimpan terpisah untuk setiap kelas.</p>
    <div class="save-bar save-bar-in"><button class="btn" type="submit"<?= $disabledEdit ?>>Simpan Nama Kelas</button><span class="hint">Tombol menempel di atas tabel.</span></div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th style="width:225px">Kelas</th><th>Nama Kelas</th></tr></thead>
        <tbody>
        <?php for ($k = 1; $k <= 6; $k++): $aktifNK = $k === (int) $c['kelas']; ?>
          <tr<?= $aktifNK ? ' style="background:var(--brand-soft)"' : '' ?>>
            <td class="center"><b><?= e(kelas_label($k)) ?></b><?= $aktifNK ? '<div class="hint">aktif</div>' : '' ?></td>
            <td><input type="text" name="nk_<?= $k ?>" value="<?= e(nama_kelas($k)) ?>" placeholder="mis. Ustman Bin Affan"<?= $disabledEdit ?>></td>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<div class="card">
  <h3>Logo Profil &amp; Sampul Raport</h3>
  <?php if (!is_admin()): ?>
    <div class="alert alert-info">Hanya administrator yang dapat mengunggah logo.</div>
  <?php else: ?>
    <div class="hint mb">Logo tampil di bagian atas sampul halaman pertama, sebagai watermark background aplikasi (kecil berulang), logo besar di pojok kanan atas, serta ikon di sidebar &amp; halaman login. Tidak ikut tercetak saat cetak raport. Format PNG/JPG/JPEG/WEBP/GIF/SVG, maksimal 2 MB.</div>
    <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <?php if (trim($p['logo']) !== ''): ?>
        <img src="data/<?= e($p['logo']) ?>" alt="Logo sampul" style="max-height:72px;max-width:150px;border:1px solid var(--line);border-radius:8px;background:#fff;padding:6px">
      <?php else: ?>
        <span class="hint">Belum ada logo — tampil judul tanpa logo.</span>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-top:12px">
      <form method="post" action="index.php?page=profil" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="logo">
        <div class="field" style="margin-bottom:0"><label>Pilih File Logo</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.gif,.svg" required></div>
        <button class="btn" type="submit">Import Logo</button>
      </form>
      <?php if (trim($p['logo']) !== ''): ?>
        <form method="post" action="index.php?page=profil" data-confirm="Hapus logo sampul?">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="logo_hapus">
          <button class="btn danger" type="submit">Hapus Logo</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="grid grid-2">
  <form method="post" action="index.php?page=profil" data-confirm="Simpan data wali kelas seluruh kelas &amp; semester?">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="walikelas">
    <div class="card">
      <h3>Wali Kelas (untuk tanda tangan raport)</h3>
      <?php if (!can_edit()): ?>
        <div class="alert alert-info">Hanya administrator dan wali kelas yang dapat mengubah data ini.</div>
      <?php else: ?>
        <div class="hint mb">Diisi sendiri oleh pengguna untuk <b>setiap kelas dan semester</b>. Tanda tangan raport mengikuti kelas &amp; semester konteks. Baris <b>konteks aktif</b> ditandai.</div>
      <?php endif; ?>
      <div class="save-bar save-bar-in"><button class="btn" type="submit"<?= $disabledEdit ?>>Simpan Wali Kelas</button><span class="hint">Tombol menempel di atas tabel — simpan tanpa menggulir ke bawah.</span></div>
      <?php for ($s = 1; $s <= 2; $s++): ?>
        <h4 style="margin:14px 0 6px">Semester <?= $s ?> <?= $s === (int) $p['semester'] ? '<span class="badge badge-blue">konteks aktif</span>' : '' ?></h4>
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th style="width:225px">Kelas</th><th>Nama Wali Kelas</th><th>NBM</th></tr></thead>
            <tbody>
            <?php for ($k = 1; $k <= 6; $k++):
                $w = walikelas($k, $s);
                $aktifK = $k === (int) $p['kelas_aktif'] && $s === (int) $p['semester']; ?>
              <tr<?= $aktifK ? ' style="background:var(--brand-soft)"' : '' ?>>
                <td class="center"><b><?= e(kelas_label($k)) ?></b><?= $aktifK ? '<div class="hint">aktif</div>' : '' ?></td>
                <td><input type="text" name="wk_nama_<?= $k ?>_<?= $s ?>" value="<?= e($w['nama']) ?>"<?= $disabledEdit ?>></td>
                <td><input type="text" name="wk_nip_<?= $k ?>_<?= $s ?>" value="<?= e($w['nip']) ?>"<?= $disabledEdit ?>></td>
              </tr>
            <?php endfor; ?>
            </tbody>
          </table>
        </div>
      <?php endfor; ?>
    </div>
  </form>

  <div class="card">
    <h3>Naik Kelas</h3>
    <p class="hint">Kenaikan kelas berjalan <b>otomatis saat tahun ajaran naik</b> — seluruh siswa aktif otomatis dinaikkan satu tingkat kelas (kelas 6 tidak berubah), tanpa perlu tombol. Riwayat nilai semester sebelumnya tetap tersimpan dan dipakai sebagai pembanding kenaikan nilai di menu Mata Pelajaran.</p>
    <div class="alert alert-warning mt">
      <b>Konteks saat ini:</b> <?= e(kelas_label((int) $c['kelas'])) ?>, <?= e(fase_label($c['fase'])) ?>, Semester <?= $c['semester'] ?>, Tahun Ajaran <?= e($c['ta']) ?>.
    </div>
  </div>
</div>
