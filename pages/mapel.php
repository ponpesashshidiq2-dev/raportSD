<?php
$c = ctx();
$tab = ($_GET['tab'] ?? 'daftar') === 'monitor' ? 'monitor' : 'daftar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin()) {
        flash_set('error', 'Hanya administrator yang dapat mengubah mata pelajaran.');
        redirect('mapel');
    }
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $kkm = trim($_POST['kkm'] ?? '');
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'muatan' => in_array($_POST['muatan'] ?? '', ['agama', 'nasional', 'lokal'], true) ? $_POST['muatan'] : 'nasional',
            'kelas_min' => min(6, max(1, (int) ($_POST['kelas_min'] ?? 1))),
            'kelas_max' => min(6, max(1, (int) ($_POST['kelas_max'] ?? 6))),
            'kkm' => ($kkm === '' ? null : max(0, min(100, (int) $kkm))),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            'urutan' => (int) ($_POST['urutan'] ?? 0),
        ];
        if ($data['nama'] === '') {
            flash_set('error', 'Nama mata pelajaran wajib diisi.');
            redirect('mapel');
        }
        if ($data['kelas_max'] < $data['kelas_min']) {
            $data['kelas_max'] = $data['kelas_min'];
        }
        if ($id > 0) {
            update_row('mapel', $data, 'id=?', [$id]);
            flash_set('success', 'Mata pelajaran diperbarui.');
        } else {
            $data['urutan'] = $data['urutan'] ?: ((int) scalar('SELECT COALESCE(MAX(urutan),0) FROM mapel') + 1);
            insert_row('mapel', $data);
            flash_set('success', 'Mata pelajaran ditambahkan.');
        }
        redirect('mapel');
    }
    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        run('DELETE FROM nilai_formatif WHERE mapel_id=?', [$id]);
        run('DELETE FROM nilai_sumatif WHERE mapel_id=?', [$id]);
        run('DELETE FROM sumatif_jenis WHERE mapel_id=?', [$id]);
        run('DELETE FROM tp WHERE mapel_id=?', [$id]);
        run('DELETE FROM cp WHERE mapel_id=?', [$id]);
        run('DELETE FROM mapel WHERE id=?', [$id]);
        flash_set('success', 'Mata pelajaran beserta data terkait dihapus.');
        redirect('mapel');
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM mapel WHERE id=?', [(int) $_GET['edit']]);
}
$semuaMapel = all('SELECT * FROM mapel ORDER BY muatan, urutan, nama');
$mapelKelas = mapel_kelas($c['kelas']);
$muatanLabel = ['agama' => 'Muatan Agama', 'nasional' => 'Muatan Nasional', 'lokal' => 'Muatan Lokal'];

page_head('Mata Pelajaran', 'Kelola mapel tiga muatan, KKM, dan monitoring kenaikan nilai dibanding semester/kelas sebelumnya.',
    '<a class="btn secondary" href="index.php?page=mapel&tab=daftar">Daftar Mapel</a>'
    . '<a class="btn ' . ($tab === 'monitor' ? 'secondary' : 'blue') . '" href="index.php?page=mapel&tab=monitor">Monitoring Nilai</a>');
echo context_bar();
?>
<div class="tabs no-print">
  <a class="tab<?= $tab === 'daftar' ? ' active' : '' ?>" href="index.php?page=mapel&tab=daftar">Daftar Mapel</a>
  <a class="tab<?= $tab === 'monitor' ? ' active' : '' ?>" href="index.php?page=mapel&tab=monitor">Monitoring Nilai (Merah = Turun)</a>
</div>

<?php if ($tab === 'daftar'): ?>

  <?php if (is_admin()): ?>
  <form class="card" method="post" action="index.php?page=mapel">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h3><?= $edit ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran' ?></h3>
    <div class="form-row">
      <div class="field"><label>Nama Mapel</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
      <div class="field">
        <label>Muatan</label>
        <select name="muatan">
          <?php foreach ($muatanLabel as $v => $lbl): ?>
            <option value="<?= $v ?>"<?= ($edit['muatan'] ?? 'nasional') === $v ? ' selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Kelas Minimal</label><select name="kelas_min"><?php for ($k = 1; $k <= 6; $k++): ?><option value="<?= $k ?>"<?= (int) ($edit['kelas_min'] ?? 1) === $k ? ' selected' : '' ?>><?= $k ?></option><?php endfor; ?></select></div>
      <div class="field"><label>Kelas Maksimal</label><select name="kelas_max"><?php for ($k = 1; $k <= 6; $k++): ?><option value="<?= $k ?>"<?= (int) ($edit['kelas_max'] ?? 6) === $k ? ' selected' : '' ?>><?= $k ?></option><?php endfor; ?></select></div>
      <div class="field"><label>KKM</label><input type="number" name="kkm" min="0" max="100" value="<?= $edit && $edit['kkm'] !== null && $edit['kkm'] !== '' ? (int) $edit['kkm'] : '' ?>" placeholder="Kosongkan jika tidak tampil"></div>
      <div class="field"><label>Urutan</label><input type="number" name="urutan" value="<?= (int) ($edit['urutan'] ?? 0) ?>"></div>
    </div>
    <label class="check mb"><input type="checkbox" name="aktif" value="1"<?= !$edit || $edit['aktif'] ? ' checked' : '' ?>> Aktif</label>
    <div class="page-actions">
      <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Mapel' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="index.php?page=mapel">Batal</a><?php endif; ?>
    </div>
  </form>
  <?php elseif ($edit): ?>
    <div class="alert alert-info">Hanya administrator yang dapat mengubah mata pelajaran.</div>
  <?php endif; ?>

  <div class="alert alert-info">
    Mapel dengan <b>KKM terisi</b> akan tampil pada raport cetak untuk konteks kelas <?= $c['kelas'] ?>.
    Saat ini <b><?= count(mapel_raport($c['kelas'])) ?></b> dari <?= count($mapelKelas) ?> mapel aktif tampil di raport.
  </div>

  <?php foreach ($muatanLabel as $muatan => $lbl):
      $list = array_values(array_filter($mapelKelas, fn($m) => $m['muatan'] === $muatan));
      if (!$list) continue; ?>
    <div class="card group-<?= $muatan ?>">
      <div class="muatan-head"><h3><?= $lbl ?></h3><span class="badge badge-gray"><?= count($list) ?> mapel</span></div>
      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th class="center" style="width:40px">No</th><th>Mata Pelajaran</th>
              <th class="center">KKM</th><th class="center">Kelas</th><th class="center">TP</th>
              <th class="center">Di Raport</th><th class="center">Status</th><?php if (is_admin()): ?><th class="center">Aksi</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
          <?php $no = 0; foreach ($list as $m): $no++;
              $tpN = (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND fase=?', [$m['id'], $c['fase']]);
              $adaKkm = $m['kkm'] !== null && $m['kkm'] !== ''; ?>
            <tr>
              <td class="center"><?= $no ?></td>
              <td class="name-cell"><?= e($m['nama']) ?></td>
              <td class="center"><?= $adaKkm ? '<b>' . (int) $m['kkm'] . '</b>' : '<span class="badge badge-gray">-</span>' ?></td>
              <td class="center"><?= (int) $m['kelas_min'] ?>-<?= (int) $m['kelas_max'] ?></td>
              <td class="center"><?= $tpN ?><div class="hint"><?= e($c['fase']) ?></div></td>
              <td class="center"><?= $adaKkm ? '<span class="badge badge-green">Ya</span>' : '<span class="badge badge-amber">Tidak</span>' ?></td>
              <td class="center"><?= $m['aktif'] ? '<span class="badge badge-blue">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
              <?php if (is_admin()): ?>
              <td class="center">
                <a class="btn sm secondary" href="index.php?page=mapel&edit=<?= (int) $m['id'] ?>">Ubah</a>
                <form method="post" action="index.php?page=mapel" data-confirm="Hapus mapel beserta nilai dan TP-nya?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button class="btn sm danger" type="submit">Hapus</button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

<?php else:

  $siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$c['kelas']]);
  $kolom = mapel_raport($c['kelas']);
  $turun = 0; $naik = 0; $baru = 0; $bawahKKM = 0;

  if ($kolom && $siswas) {
      foreach ($kolom as $m) {
          foreach ($siswas as $s) {
              $now = nilai_akhir((int) $s['id'], (int) $m['id'], $c['ta'], $c['semester']);
              if ($now === null) continue;
              $prev = nilai_sebelumnya((int) $s['id'], (int) $m['id'], $c['ta'], $c['semester']);
              if ($prev === null) $baru++;
              elseif ($now < $prev) $turun++;
              else $naik++;
              $kkm = $m['kkm'] !== null && $m['kkm'] !== '' ? (int) $m['kkm'] : null;
              if ($kkm !== null && $now < $kkm) $bawahKKM++;
          }
      }
  }
  ?>
  <div class="grid grid-4 mb">
    <div class="stat"><div class="stat-k">Nilai Naik / Sama</div><div class="stat-v trend-up"><?= $naik ?></div><div class="stat-s">dibanding semester/kelas sebelumnya</div></div>
    <div class="stat danger"><div class="stat-k">Nilai Turun</div><div class="stat-v nilai-red"><?= $turun ?></div><div class="stat-s">ditandai merah</div></div>
    <div class="stat blue"><div class="stat-k">Belum Ada Pembanding</div><div class="stat-v"><?= $baru ?></div><div class="stat-s">semester pertama siswa</div></div>
    <div class="stat warn"><div class="stat-k">Di Bawah KKM</div><div class="stat-v"><?= $bawahKKM ?></div><div class="stat-s">perlu tindak lanjut</div></div>
  </div>

  <div class="alert alert-info">
    Setiap nilai raport dibandingkan dengan nilai siswa yang sama pada semester/kelas sebelumnya.
    Sel merah berarti nilai <b>turun</b> dan harus dinaikkan kembali.
  </div>

  <?php if (!$kolom): ?>
    <div class="card empty">Belum ada mata pelajaran dengan KKM untuk konteks kelas ini. Isi KKM pada tab <a href="index.php?page=mapel&tab=daftar">Daftar Mapel</a>.</div>
  <?php elseif (!$siswas): ?>
    <div class="card empty">Belum ada siswa pada kelas <?= $c['kelas'] ?>. <a href="index.php?page=siswa">Tambah data siswa</a>.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th class="center" style="width:40px">No</th>
          <th>Nama Siswa</th>
          <?php foreach ($kolom as $m): ?>
            <th class="center" title="<?= e($m['nama']) ?>"><?= e(mb_strimwidth($m['nama'], 0, 16, '…')) ?><div class="hint">KKM <?= (int) $m['kkm'] ?></div></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php $no = 0; foreach ($siswas as $s): $no++; ?>
        <tr>
          <td class="center"><?= $no ?></td>
          <td class="name-cell"><?= e($s['nama']) ?></td>
          <?php foreach ($kolom as $m):
              $now = nilai_akhir((int) $s['id'], (int) $m['id'], $c['ta'], $c['semester']);
              if ($now === null) {
                  echo '<td class="center muted">-</td>';
                  continue;
              }
              $prev = nilai_sebelumnya((int) $s['id'], (int) $m['id'], $c['ta'], $c['semester']);
              $turunnih = $prev !== null && $now < $prev;
              $kkm = $m['kkm'] !== null && $m['kkm'] !== '' ? (int) $m['kkm'] : null;
              $kls = $turunnih ? ' class="center naik"' : ' class="center"';
              echo '<td' . $kls . '>';
              echo '<b' . ($kkm !== null && $now < $kkm ? ' class="nilai-red"' : '') . '>' . format_nilai($now) . '</b>';
              if ($turunnih) {
                  echo ' <span class="trend-down">&darr;' . format_nilai($prev) . '</span>';
              } elseif ($prev !== null && $now > $prev) {
                  echo '<div class="trend-up">&uarr;' . format_nilai($prev) . '</div>';
              } else {
                  echo '<div class="trend-new">' . ($prev === null ? 'baru' : '=' . format_nilai($prev)) . '</div>';
              }
              echo '</td>';
          endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
<?php endif; ?>
