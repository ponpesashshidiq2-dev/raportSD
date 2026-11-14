<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? (int) $c['kelas'];
$mapelList = mapel_kelas($c['kelas']);
$mapelId = (int) ($_GET['mapel'] ?? 0);
if ($mapelId <= 0 && $mapelList) {
    $mapelId = (int) $mapelList[0]['id'];
}
$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$kelasSiswa]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_sumatif');
    }
    $aksi = $_POST['aksi'] ?? '';
    $mapelId = (int) ($_POST['mapel_id'] ?? $mapelId);

    if ($aksi === 'jenis_tambah') {
        $nama = trim($_POST['nama'] ?? '');
        if ($nama === '' || $mapelId <= 0) {
            flash_set('error', 'Nama penilaian wajib diisi.');
        } else {
            $ada = one('SELECT id FROM sumatif_jenis WHERE mapel_id=? AND ta=? AND semester=? AND LOWER(nama)=?',
                [$mapelId, $c['ta'], $c['semester'], mb_strtolower($nama)]);
            if ($ada) {
                flash_set('error', 'Nama penilaian sudah ada.');
            } else {
                insert_row('sumatif_jenis', [
                    'mapel_id' => $mapelId, 'ta' => $c['ta'], 'semester' => $c['semester'],
                    'nama' => $nama, 'tanggal' => trim($_POST['tanggal'] ?? ''),
                ]);
                flash_set('success', 'Penilaian sumatif "' . $nama . '" ditambahkan.');
            }
        }
        redirect('nilai_sumatif', ['mapel' => $mapelId]);
    }

    if ($aksi === 'jenis_hapus') {
        $jid = (int) ($_POST['jenis_id'] ?? 0);
        run('DELETE FROM nilai_sumatif WHERE jenis_id=?', [$jid]);
        run('DELETE FROM sumatif_jenis WHERE id=?', [$jid]);
        flash_set('success', 'Penilaian sumatif beserta nilainya dihapus.');
        redirect('nilai_sumatif', ['mapel' => $mapelId]);
    }

    if ($aksi === 'simpan') {
        $jenis = all('SELECT * FROM sumatif_jenis WHERE mapel_id=? AND ta=? AND semester=? ORDER BY id',
            [$mapelId, $c['ta'], $c['semester']]);
        $nilai = $_POST['nilai'] ?? [];
        $tersimpan = 0;
        db()->beginTransaction();
        try {
            foreach ($jenis as $j) {
                foreach ($siswas as $s) {
                    $key = $j['id'] . '_' . $s['id'];
                    $raw = trim((string) ($nilai[$key] ?? ''));
                    $ada = one('SELECT id FROM nilai_sumatif WHERE siswa_id=? AND jenis_id=? AND ta=? AND semester=?',
                        [$s['id'], $j['id'], $c['ta'], $c['semester']]);
                    if ($raw === '') {
                        if ($ada) {
                            run('DELETE FROM nilai_sumatif WHERE id=?', [$ada['id']]);
                        }
                        continue;
                    }
                    $v = (float) str_replace(',', '.', $raw);
                    if ($v < 0) $v = 0;
                    if ($v > 100) $v = 100;
                    $row = ['nilai' => $v, 'kelas' => $c['kelas']];
                    if ($ada) {
                        update_row('nilai_sumatif', $row, 'id=?', [$ada['id']]);
                    } else {
                        insert_row('nilai_sumatif', $row + [
                            'siswa_id' => $s['id'], 'mapel_id' => $mapelId, 'jenis_id' => $j['id'],
                            'ta' => $c['ta'], 'semester' => $c['semester'],
                        ]);
                    }
                    $tersimpan++;
                }
            }
            db()->commit();
            flash_set('success', "Nilai sumatif tersimpan: $tersimpan sel.");
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
        }
        redirect('nilai_sumatif', ['mapel' => $mapelId]);
    }
}

$jenisList = $mapelId > 0 ? all('SELECT * FROM sumatif_jenis WHERE mapel_id=? AND ta=? AND semester=? ORDER BY id',
    [$mapelId, $c['ta'], $c['semester']]) : [];

page_head('Input Nilai Sumatif', 'Buat penilaian sumatif (mis. harian, tengah semester, akhir semester) lalu isi nilainya.');
echo context_bar();
echo angkatan_lock_alert();
?>
<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="nilai_sumatif">
  <label style="margin:0">Mata Pelajaran</label>
  <select name="mapel" onchange="this.form.submit()">
    <?php foreach ($mapelList as $m): ?>
      <option value="<?= (int) $m['id'] ?>"<?= $mapelId === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nama']) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="badge badge-gray"><?= count($jenisList) ?> penilaian</span>
</form>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran untuk kelas <?= $c['kelas'] ?>.</div>
<?php else: ?>
<?php if (can_edit()): ?>
<div class="card">
  <h3>Tambah Penilaian Sumatif</h3>
  <form method="post" action="index.php?page=nilai_sumatif">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="jenis_tambah">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <div class="form-row">
      <div class="field"><label>Nama Penilaian</label><input type="text" name="nama" placeholder="mis. Sumatif Akhir Semester" required></div>
      <div class="field"><label>Tanggal</label><input type="date" name="tanggal"></div>
    </div>
    <button class="btn" type="submit">Tambah Kolom</button>
  </form>
</div>
<?php endif; ?>

<?php if (!$jenisList): ?>
  <div class="card empty">Belum ada penilaian sumatif untuk mapel ini pada <?= e($c['ta']) ?> Semester <?= $c['semester'] ?>. Tambahkan kolom penilaian terlebih dahulu.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php else:
  $existing = [];
  foreach (all('SELECT jenis_id, siswa_id, nilai FROM nilai_sumatif WHERE mapel_id=? AND ta=? AND semester=?',
      [$mapelId, $c['ta'], $c['semester']]) as $r) {
      $existing[$r['jenis_id'] . '_' . $r['siswa_id']] = $r['nilai'];
  }
  ?>
  <form method="post" action="index.php?page=nilai_sumatif">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <div class="table-wrap">
      <table class="tbl">
        <caption><?= e($mapel['nama']) ?> - Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead>
          <tr>
            <th class="center" style="width:40px">No</th>
            <th>Nama Siswa</th>
            <?php foreach ($jenisList as $j): ?>
              <th class="center">
                <?= e($j['nama']) ?><div class="hint" style="font-weight:400;text-transform:none;letter-spacing:0"><?= e($j['tanggal']) ?></div>
                <?php if (can_edit()):
                  $fid = 'deljenis' . (int) $j['id']; ?>
                <form id="<?= $fid ?>" method="post" action="index.php?page=nilai_sumatif" data-confirm="Hapus penilaian beserta seluruh nilainya?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="jenis_hapus">
                  <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
                  <input type="hidden" name="jenis_id" value="<?= (int) $j['id'] ?>">
                </form>
                <button class="btn sm danger" type="submit" form="<?= $fid ?>">Hapus</button>
                <?php endif; ?>
              </th>
            <?php endforeach; ?>
            <th class="center">Rata Sumatif</th>
            <th class="center">Nilai Akhir</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++;
            $sum = 0; $cnt = 0; ?>
          <tr>
            <td class="center"><?= $no ?></td>
            <td class="name-cell"><?= e($s['nama']) ?><div class="hint">NISN <?= e($s['nisn']) ?></div></td>
            <?php foreach ($jenisList as $j):
                $k = $j['id'] . '_' . $s['id'];
                $v = $existing[$k] ?? null;
                if ($v !== null) { $sum += (float) $v; $cnt++; } ?>
              <td class="center">
                <input type="number" name="nilai[<?= $k ?>]" min="0" max="100" step="0.5" value="<?= $v !== null ? e(format_nilai((float) $v)) : '' ?>" placeholder="-">
              </td>
            <?php endforeach; ?>
            <td class="center"><b><?= $cnt ? format_nilai($sum / $cnt) : '-' ?></b></td>
            <?php $na = nilai_akhir((int) $s['id'], $mapelId, $c['ta'], $c['semester']); ?>
            <td class="center"><b class="<?= $na !== null && $na < (kkm_mapel($mapelId) ?? 0) ? 'nilai-red' : '' ?>"><?= $na !== null ? format_nilai($na) : '-' ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="toolbar mt">
      <?php if (can_edit()): ?><button class="btn" type="submit">Simpan Nilai Sumatif</button><?php endif; ?>
      <span class="hint">Nilai Akhir = formatif &times; <?= (int) profil()['bobot_formatif'] ?>% + sumatif &times; <?= (int) profil()['bobot_sumatif'] ?>%.</span>
    </div>
  </form>
<?php endif; ?>
<?php endif; ?>
