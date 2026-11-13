<?php
$p = profil();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan' && can_edit()) {
        $konteks = [
            'ta' => trim($_POST['ta'] ?? ''),
            'semester' => in_array((int) ($_POST['semester'] ?? 1), [1, 2], true) ? (int) $_POST['semester'] : 1,
            'kelas_aktif' => min(6, max(1, (int) ($_POST['kelas_aktif'] ?? 1))),
            'fase' => in_array($_POST['fase'] ?? '', ['A', 'B', 'C'], true) ? $_POST['fase'] : 'A',
        ];
        if (!preg_match('/^\d{4}\/\d{4}$/', $konteks['ta'])) {
            flash_set('error', 'Format tahun ajaran harus 2025/2026.');
            redirect('profil');
        }
        if (is_admin()) {
            foreach (['sekolah', 'npsn', 'alamat', 'kota', 'telp', 'email', 'website', 'kepsek', 'kepsek_nip'] as $f) {
                if (isset($_POST[$f])) {
                    $konteks[$f] = trim($_POST[$f]);
                }
            }
            $konteks['bobot_formatif'] = max(0, min(100, (int) ($_POST['bobot_formatif'] ?? 40)));
            $konteks['bobot_sumatif'] = max(0, min(100, (int) ($_POST['bobot_sumatif'] ?? 60)));
        }
        update_row('profil', $konteks, 'id=1', [1]);
        flash_set('success', 'Profil dan konteks raport tersimpan.');
        redirect('profil');
    }

    if ($aksi === 'walikelas' && is_admin()) {
        for ($k = 1; $k <= 6; $k++) {
            $nama = trim($_POST['wk_nama_' . $k] ?? '');
            $nip = trim($_POST['wk_nip_' . $k] ?? '');
            if (one('SELECT kelas FROM walikelas WHERE kelas=?', [$k])) {
                update_row('walikelas', ['nama' => $nama, 'nip' => $nip], 'kelas=?', [$k]);
            } else {
                insert_row('walikelas', ['kelas' => $k, 'nama' => $nama, 'nip' => $nip]);
            }
        }
        flash_set('success', 'Data wali kelas tersimpan.');
        redirect('profil');
    }

    if ($aksi === 'naik_kelas' && is_admin()) {
        $n = run("UPDATE siswa SET kelas = kelas + 1 WHERE status='aktif' AND kelas < 6");
        flash_set('success', "$n siswa dinaikkan satu tingkat kelas.");
        redirect('profil');
    }
}

$disabled = is_admin() ? '' : ' disabled';
$c = ctx();
page_head('Profil & Konteks Raport', 'Identitas sekolah, tahun ajaran, semester, kelas aktif, dan wali kelas.');
?>
<form method="post" action="index.php?page=profil">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">

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
        <div class="field"><label>NIP Kepala Sekolah</label><input type="text" name="kepsek_nip" value="<?= e($p['kepsek_nip']) ?>"<?= $disabled ?>></div>
      </div>
    </div>

    <div class="card">
      <h3>Konteks Raport</h3>
      <div class="hint mb">Seluruh input nilai dan cetak raport mengikuti konteks di bawah ini.</div>
      <div class="form-row">
        <div class="field">
          <label>Tahun Ajaran</label>
          <input type="text" name="ta" value="<?= e($p['ta']) ?>" placeholder="2025/2026" required>
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
          <select name="kelas_aktif">
            <?php for ($k = 1; $k <= 6; $k++): ?>
              <option value="<?= $k ?>"<?= (int) $p['kelas_aktif'] === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
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
          <label>Bobot Formatif (%)</label>
          <input type="number" name="bobot_formatif" min="0" max="100" value="<?= (int) $p['bobot_formatif'] ?>"<?= $disabled ?>>
        </div>
        <div class="field">
          <label>Bobot Sumatif (%)</label>
          <input type="number" name="bobot_sumatif" min="0" max="100" value="<?= (int) $p['bobot_sumatif'] ?>"<?= $disabled ?>>
        </div>
      </div>
      <div class="hint mb">Nilai Akhir = rata-rata formatif &times; bobot + rata-rata sumatif &times; bobot.</div>
      <button class="btn" type="submit">Simpan Profil &amp; Konteks</button>
    </div>
  </div>
</form>

<div class="grid grid-2">
  <form method="post" action="index.php?page=profil" data-confirm="Simpan data wali kelas seluruh tingkatan?">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="walikelas">
    <div class="card">
      <h3>Wali Kelas (untuk tanda tangan raport)</h3>
      <?php if (!is_admin()): ?><div class="alert alert-info">Hanya administrator yang dapat mengubah data ini.</div><?php endif; ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th style="width:90px">Kelas</th><th>Nama Wali Kelas</th><th>NIP</th></tr></thead>
          <tbody>
          <?php for ($k = 1; $k <= 6; $k++):
              $w = walikelas($k); ?>
            <tr>
              <td class="center"><b><?= $k ?></b></td>
              <td><input type="text" name="wk_nama_<?= $k ?>" value="<?= e($w['nama']) ?>"<?= $disabled ?>></td>
              <td><input type="text" name="wk_nip_<?= $k ?>" value="<?= e($w['nip']) ?>"<?= $disabled ?>></td>
            </tr>
          <?php endfor; ?>
          </tbody>
        </table>
      </div>
      <div class="mt"><button class="btn" type="submit"<?= $disabled ?>>Simpan Wali Kelas</button></div>
    </div>
  </form>

  <div class="card">
    <h3>Naik Kelas</h3>
    <p class="hint">Menaikkan seluruh siswa aktif satu tingkat kelas (kelas 6 tidak berubah). Riwayat nilai semester sebelumnya tetap tersimpan dan digunakan untuk pembanding kenaikan nilai di menu Mata Pelajaran.</p>
    <p class="hint">Nilai pada semester/kelas sebelumnya wajib lebih tinggi; jika turun, akan ditandai merah pada menu Mata Pelajaran.</p>
    <?php if (is_admin()): ?>
      <form method="post" action="index.php?page=profil" data-confirm="Naikkan seluruh siswa aktif satu tingkat kelas?">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="naik_kelas">
        <button class="btn blue" type="submit">Naikkan Kelas Seluruh Siswa</button>
      </form>
    <?php else: ?>
      <div class="alert alert-info">Hanya administrator yang dapat menaikkan kelas.</div>
    <?php endif; ?>
    <div class="alert alert-warning mt">
      <b>Konteks saat ini:</b> Kelas <?= $c['kelas'] ?>, <?= e(fase_label($c['fase'])) ?>, Semester <?= $c['semester'] ?>, Tahun Ajaran <?= e($c['ta']) ?>.
    </div>
  </div>
</div>
